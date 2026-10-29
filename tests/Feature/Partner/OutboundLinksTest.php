<?php

use App\Models\User;
use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Compare\Models\FinancialProduct;
use App\Modules\Core\Enums\Role;
use App\Modules\Partner\Actions\RecordClick;
use App\Modules\Partner\Filament\Resources\OutboundLinks\Pages\ManageOutboundLinks;
use App\Modules\Partner\Models\OutboundClick;
use App\Modules\Partner\Models\OutboundLink;
use App\Modules\Partner\PartnerServiceProvider;
use App\Modules\Partner\Support\BotDetector;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;
use Laravel\Pennant\Feature;
use Livewire\Livewire;

uses(RefreshDatabase::class);

const BROWSER = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1';

function partnerCard(string $name = 'Thẻ Đối Tác'): FinancialProduct
{
    $card = FinancialProduct::factory()->published()->create([
        'name' => $name,
        'slug' => str($name)->slug()->toString(),
        'institution_id' => FinancialInstitution::factory()->create()->id,
    ]);
    $card->creditCard()->create(['annual_fee' => 0]);

    return $card;
}

function go(OutboundLink $link, array $headers = ['User-Agent' => BROWSER])
{
    return test()->withHeaders($headers)->get('http://ezfin.test'.$link->url());
}

it('creates unguessable codes that never change', function () {
    $links = OutboundLink::factory()->count(20)->create();

    expect($links->pluck('code')->unique())->toHaveCount(20)
        ->and($links->pluck('code')->every(fn ($code) => preg_match('/^[A-Za-z0-9]{12}$/', $code)))->toBeTrue();

    $link = $links->first();
    $code = $link->code;
    $link->update(['code' => 'aaaaaaaaaaaa', 'name' => 'Đổi tên']);

    expect($link->fresh()->code)->toBe($code);
});

it('logs a click and sends the visitor to the partner with the click id', function () {
    $link = OutboundLink::factory()->create();

    $response = go($link, ['User-Agent' => BROWSER, 'Referer' => 'http://ezfin.test/the-tin-dung?phi=mien-phi'])
        ->assertStatus(302)
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

    $click = OutboundClick::sole();
    expect($response->headers->get('Location'))->toBe('https://partner.example/apply?sub='.$click->click_id)
        ->and($click->click_id)->toMatch('/^[A-Za-z0-9]{24}$/')
        ->and($click->referer_path)->toBe('/the-tin-dung')
        ->and($click->ip_hash)->toHaveLength(64)
        ->and($click->ip_hash)->not->toContain('127.0.0.1')
        ->and($click->user_agent)->toBe(BROWSER);
    expect($response->headers->get('Cache-Control'))->toContain('no-store');
});

it('does not keep referers from other sites', function () {
    go(OutboundLink::factory()->create(), ['User-Agent' => BROWSER, 'Referer' => 'https://other.example/trang?x=1'])->assertStatus(302);

    expect(OutboundClick::sole()->referer_path)->toBeNull();
});

it('redirects bots without logging them', function (?string $userAgent) {
    $link = OutboundLink::factory()->create();

    go($link, ['User-Agent' => $userAgent])->assertStatus(302)->assertRedirect('https://partner.example/apply?sub=');

    expect(OutboundClick::count())->toBe(0);
})->with([
    'Googlebot' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
    'Facebook preview' => 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
    'curl' => 'curl/8.5.0',
    'headless Chrome' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/120.0 Safari/537.36',
    'empty' => '',
]);

it('recognises real browsers', function () {
    expect(BotDetector::isBot(BROWSER))->toBeFalse()
        ->and(BotDetector::isBot('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36 Edg/130.0'))->toBeFalse();
});

it('counts repeated clicks from one visitor only up to the limit', function () {
    $link = OutboundLink::factory()->create();

    foreach (range(1, RecordClick::MAX_PER_MINUTE + 3) as $i) {
        go($link)->assertStatus(302);
    }

    expect(OutboundClick::count())->toBe(RecordClick::MAX_PER_MINUTE);
});

it('rate limits the redirect per visitor', function () {
    $link = OutboundLink::factory()->create();

    foreach (range(1, PartnerServiceProvider::REDIRECTS_PER_MINUTE) as $i) {
        go($link)->assertStatus(302);
    }

    go($link)->assertStatus(429);
});

it('returns 404 for unknown or inactive links and when the partner module is off', function () {
    $this->get('http://ezfin.test/di/AAAAAAAAAAAA')->assertNotFound();
    $this->get('http://ezfin.test/di/ngan')->assertNotFound();
    go(OutboundLink::factory()->inactive()->create())->assertNotFound();

    $link = OutboundLink::factory()->create();
    Feature::deactivate('partner');
    go($link)->assertNotFound();
    expect(OutboundClick::count())->toBe(0);
});

it('keeps /di/ out of search engines', function () {
    $this->get('http://ezfin.test/robots.txt')->assertSee('Disallow: /di/');
    OutboundLink::factory()->create();
    $this->get('http://ezfin.test/sitemap.xml')->assertDontSee('/di/');
});

it('rejects destinations that are not https', function (string $url) {
    OutboundLink::factory()->create(['destination_url' => $url]);
})->with(['http://partner.example', 'javascript:alert(1)', '//partner.example', 'https://'])->throws(ValidationException::class);

it('shows the partner button with sponsored rel and the disclosure only on products with an active link', function () {
    $card = partnerCard('Thẻ Có Link');
    partnerCard('Thẻ Không Link');
    $link = OutboundLink::factory()->create(['product_id' => $card->id, 'cta_label' => 'Mở thẻ ngay']);

    $this->get('http://ezfin.test/the-tin-dung')->assertOk()
        ->assertSee('href="'.$link->url().'"', false)
        ->assertSee('rel="sponsored nofollow noopener"', false)
        ->assertSee('Mở thẻ ngay')
        ->assertSee('liên kết đối tác');

    $this->get(route('compare.credit-cards.show', $card->slug))->assertOk()
        ->assertSee('href="'.$link->url().'"', false)
        ->assertSee('liên kết đối tác');

    // Thẻ không có link: nút đối tác chỉ có ở mục "Thẻ tương tự" (thẻ có link).
    $this->get(route('compare.credit-cards.show', 'the-khong-link'))->assertOk()
        ->assertSeeInOrder(['Thẻ tương tự', 'href="'.$link->url().'"'], false);
});

it('hides partner buttons when the link or the module is switched off', function () {
    $card = partnerCard();
    $link = OutboundLink::factory()->create(['product_id' => $card->id]);

    Feature::deactivate('partner');
    $this->get('http://ezfin.test/the-tin-dung')->assertOk()->assertDontSee($link->url(), false)->assertDontSee('liên kết đối tác');

    Feature::activate('partner');
    $link->update(['is_active' => false]);
    $this->get('http://ezfin.test/the-tin-dung')->assertOk()->assertDontSee($link->url(), false);
});

it('deletes click logs past the retention period', function () {
    $link = OutboundLink::factory()->create();
    foreach ([OutboundClick::RETENTION_MONTHS * 31 + 5, 10] as $i => $daysAgo) {
        OutboundClick::create(['link_id' => $link->id, 'click_id' => str_repeat((string) $i, 24), 'created_at' => now()->subDays($daysAgo)]);
    }

    Artisan::call('model:prune', ['--model' => [OutboundClick::class]]);

    expect(OutboundClick::pluck('click_id')->all())->toBe([str_repeat('1', 24)]);
});

it('lets super admins and the finance role manage partner links', function (Role $role, int $status) {
    Filament::setCurrentPanel('admin');
    $this->actingAs(User::factory()->admin($role)->create());

    $this->get('http://app.ezfin.test/quan-tri/lien-ket-doi-tac')->assertStatus($status);
})->with([
    'super admin' => [Role::SuperAdmin, 200],
    'finance' => [Role::Finance, 200],
    'editor' => [Role::Editor, 403],
]);

it('creates links in the admin and keeps links that have clicks', function () {
    Filament::setCurrentPanel('admin');
    $this->actingAs(User::factory()->admin(Role::Finance)->create());
    $card = partnerCard();

    Livewire::test(ManageOutboundLinks::class)
        ->callAction(CreateAction::class, ['name' => 'Link', 'destination_url' => 'http://khong-an-toan.example', 'cta_label' => 'Mở thẻ', 'is_active' => true])
        ->assertHasActionErrors(['destination_url']);

    Livewire::test(ManageOutboundLinks::class)
        ->callAction(CreateAction::class, ['name' => 'Link', 'product_id' => $card->id, 'destination_url' => 'https://doitac.example/?s={click_id}', 'cta_label' => 'Mở thẻ', 'is_active' => true])
        ->assertHasNoActionErrors();

    $link = OutboundLink::sole();
    expect($link->product_id)->toBe($card->id);

    OutboundClick::create(['link_id' => $link->id, 'click_id' => str_repeat('a', 24), 'created_at' => now()]);

    Livewire::test(ManageOutboundLinks::class)->assertActionHidden(TestAction::make('delete')->table($link));
});
