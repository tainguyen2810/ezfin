<?php

use App\Models\User;
use App\Modules\Compare\Enums\InterestPayment;
use App\Modules\Compare\Enums\SavingsChannel;
use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Compare\Models\FinancialProduct;
use App\Modules\Core\Enums\Role;
use App\Modules\Newsletter\Actions\SendRateAlerts;
use App\Modules\Newsletter\Actions\SendWeeklyDigest;
use App\Modules\Newsletter\Mail\ConfirmSubscriptionMail;
use App\Modules\Newsletter\Mail\RateAlertMail;
use App\Modules\Newsletter\Mail\WeeklyDigestMail;
use App\Modules\Newsletter\Models\Subscriber;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Pennant\Feature;

uses(RefreshDatabase::class);

function newsletterRate(float $rate, int $term = 12, int $daysAgo = 10): void
{
    $bank = FinancialInstitution::query()->firstOrCreate(['slug' => 'alpha'], FinancialInstitution::factory()->make(['short_name' => 'Alpha', 'slug' => 'alpha'])->toArray());
    $product = FinancialProduct::factory()->savings()->published()->create(['institution_id' => $bank->id]);
    $product->savingsRates()->forceCreate([
        'term_months' => $term, 'channel' => SavingsChannel::Online->value, 'interest_payment' => InterestPayment::EndOfTerm->value,
        'rate' => $rate, 'min_amount' => 0, 'effective_from' => today()->subDays($daysAgo),
    ]);
}

/** Đăng ký qua form rồi bấm link xác nhận trong thư; trả người nhận đã tạo. */
function subscribeAndConfirm(array $form): ?Subscriber
{
    test()->from('http://ezfin.test/lai-suat-tiet-kiem/12-thang')
        ->post('http://ezfin.test/nhan-tin', ['dong_y' => '1', ...$form])
        ->assertRedirect();

    $url = null;
    Mail::assertQueued(ConfirmSubscriptionMail::class, function ($mail) use (&$url) {
        $url = $mail->confirmUrl;

        return true;
    });
    test()->get($url)->assertOk()->assertSee('Xác nhận đăng ký');
    test()->post($url)->assertRedirect();

    return Subscriber::where('email', strtolower($form['email']))->first();
}

beforeEach(function () {
    Feature::activate(['compare', 'newsletter', 'content']);
    Mail::fake();
    newsletterRate(5.5);
});

it('stores nothing until the email link is confirmed, then records consent and the rate baseline', function () {
    $this->from('http://ezfin.test/lai-suat-tiet-kiem/12-thang')
        ->post('http://ezfin.test/nhan-tin', ['email' => 'An@Example.com', 'ky_han' => '12', 'dong_y' => '1'])
        ->assertRedirect('http://ezfin.test/lai-suat-tiet-kiem/12-thang#nhan-tin')
        ->assertSessionHas('newsletter');

    expect(Subscriber::count())->toBe(0);

    $subscriber = subscribeAndConfirm(['email' => 'an@example.com', 'ky_han' => '12']);

    expect($subscriber)->not->toBeNull()
        ->and($subscriber->source_path)->toBe('/lai-suat-tiet-kiem/12-thang')
        ->and($subscriber->weekly_digest)->toBeFalse()
        ->and($subscriber->alerts->sole()->term_months)->toBe(12)
        ->and((float) $subscriber->alerts->sole()->baseline_rate)->toBe(5.5);
});

it('validates consent and choices, ignores bots and limits confirmation emails per address', function () {
    $this->post('http://ezfin.test/nhan-tin', ['email' => 'a@example.com', 'ky_han' => '12'])->assertSessionHasErrors('dong_y', null, 'newsletter');
    $this->post('http://ezfin.test/nhan-tin', ['email' => 'a@example.com', 'dong_y' => '1'])->assertSessionHasErrors('ky_han', null, 'newsletter');
    $this->post('http://ezfin.test/nhan-tin', ['email' => 'a@example.com', 'ky_han' => '5', 'dong_y' => '1'])->assertSessionHasErrors('ky_han', null, 'newsletter');
    $this->post('http://ezfin.test/nhan-tin', ['email' => 'bot@example.com', 'ban_tin' => '1', 'dong_y' => '1', 'website' => 'spam'])->assertSessionHas('newsletter');
    Mail::assertNothingQueued();

    foreach (range(1, 5) as $i) {
        $this->post('http://ezfin.test/nhan-tin', ['email' => 'victim@example.com', 'ban_tin' => '1', 'dong_y' => '1']);
    }
    Mail::assertQueued(ConfirmSubscriptionMail::class, 3);
});

it('rejects tampered or expired confirmation links', function () {
    $this->get('http://ezfin.test/nhan-tin/xac-nhan/khong-hop-le')->assertOk()->assertSee('không hợp lệ hoặc đã hết hạn');
    $this->post('http://ezfin.test/nhan-tin/xac-nhan/khong-hop-le')->assertOk();

    $this->post('http://ezfin.test/nhan-tin', ['email' => 'b@example.com', 'ban_tin' => '1', 'dong_y' => '1']);
    $url = null;
    Mail::assertQueued(ConfirmSubscriptionMail::class, function ($mail) use (&$url) {
        $url = $mail->confirmUrl;

        return true;
    });

    $this->travel(8)->days();
    $this->post($url);
    expect(Subscriber::count())->toBe(0);
});

it('alerts once when the best rate rises, follows drops, and never repeats on rerun', function () {
    $subscriber = subscribeAndConfirm(['email' => 'c@example.com', 'ky_han' => '12']);

    expect(app(SendRateAlerts::class)->handle())->toBe(0);

    newsletterRate(5.9, daysAgo: 0);
    expect(app(SendRateAlerts::class)->handle())->toBe(1)
        ->and(app(SendRateAlerts::class)->handle())->toBe(0);

    Mail::assertQueued(RateAlertMail::class, fn ($mail) => $mail->hasTo('c@example.com')
        && $mail->increases[0]['from'] === 5.5 && $mail->increases[0]['to'] === 5.9
        && $mail->headers()->text['List-Unsubscribe-Post'] === 'List-Unsubscribe=One-Click');

    expect((float) $subscriber->alerts()->sole()->baseline_rate)->toBe(5.9);
});

it('sends the weekly digest at most once a week, with rates and new articles', function () {
    subscribeAndConfirm(['email' => 'd@example.com', 'ban_tin' => '1']);

    expect(app(SendWeeklyDigest::class)->handle())->toBe(1)
        ->and(app(SendWeeklyDigest::class)->handle())->toBe(0);

    Mail::assertQueued(WeeklyDigestMail::class, fn ($mail) => $mail->rates[0]['term'] === 12 && $mail->rates[0]['rate'] === 5.5);

    $this->travel(7)->days();
    expect(app(SendWeeklyDigest::class)->handle())->toBe(1);
});

it('lets the recipient change choices and unsubscribe, deleting the email', function () {
    $subscriber = subscribeAndConfirm(['email' => 'e@example.com', 'ky_han' => '12', 'ban_tin' => '1']);
    $manage = 'http://ezfin.test/nhan-tin/quan-ly/'.$subscriber->token;

    $this->get($manage)->assertOk()->assertSee('e•@example.com', false)->assertDontSee('e@example.com');

    $this->post($manage, ['ky_han' => ['6', '12']])->assertRedirect($manage);
    expect($subscriber->fresh()->weekly_digest)->toBeFalse()
        ->and($subscriber->alerts()->pluck('term_months')->sort()->values()->all())->toBe([6, 12]);

    $this->post($manage, [])->assertRedirect($manage);
    expect(Subscriber::count())->toBe(0);
    $this->get($manage)->assertOk()->assertSee('Không còn đăng ký nào');
});

it('supports one-click unsubscribe from the mailbox without a CSRF token', function () {
    $subscriber = subscribeAndConfirm(['email' => 'f@example.com', 'ban_tin' => '1']);

    $this->call('POST', 'http://ezfin.test/nhan-tin/huy/'.$subscriber->token, ['List-Unsubscribe' => 'One-Click'])
        ->assertOk()->assertSee('Đã hủy');

    expect(Subscriber::count())->toBe(0);
});

it('shows the signup form on the landing page and hides everything when the flag is off', function () {
    $this->get('http://ezfin.test/lai-suat-tiet-kiem/12-thang')->assertSee('Báo cho tôi khi lãi suất 12 tháng tăng');

    Feature::deactivate('newsletter');

    $this->get('http://ezfin.test/lai-suat-tiet-kiem/12-thang')->assertDontSee('nhan-tin-heading', false);
    $this->post('http://ezfin.test/nhan-tin', ['email' => 'g@example.com', 'ban_tin' => '1', 'dong_y' => '1'])->assertNotFound();
});

it('shows subscribers only to super admins', function (Role $role, int $status) {
    Filament::setCurrentPanel('admin');
    $this->actingAs(User::factory()->admin($role)->create());

    $this->get('http://ezfin.test/quan-tri/nguoi-nhan-tin')->assertStatus($status);
})->with([
    'super admin' => [Role::SuperAdmin, 200],
    'finance' => [Role::Finance, 403],
    'editor' => [Role::Editor, 403],
]);

it('renders every email with a manage link', function () {
    $subscriber = subscribeAndConfirm(['email' => 'h@example.com', 'ky_han' => '12', 'ban_tin' => '1']);
    $bank = FinancialInstitution::where('slug', 'alpha')->sole();

    $alert = (new RateAlertMail($subscriber, [['term' => 12, 'from' => 5.5, 'to' => 5.9, 'institution' => $bank]]))->render();
    $digest = (new WeeklyDigestMail($subscriber, app(SendWeeklyDigest::class)->content()['rates'], [['title' => 'Bài mới', 'url' => 'http://ezfin.test/kien-thuc/a/b', 'excerpt' => 'Tóm tắt']]))->render();
    $confirm = (new ConfirmSubscriptionMail('http://ezfin.test/nhan-tin/xac-nhan/x', 12, true))->render();

    expect($alert)->toContain('5,9%')->toContain($subscriber->manageUrl())
        ->and($digest)->toContain('Bài mới')->toContain('5,5%')->toContain($subscriber->manageUrl())
        ->and($confirm)->toContain('12 tháng')->toContain('Xác nhận đăng ký');
});
