<?php

use App\Models\User;
use App\Modules\Compare\Models\FinancialProduct;
use App\Modules\Content\Models\Article;
use App\Modules\Content\Models\ArticleCategory;
use App\Modules\Core\Actions\RecordRedirect;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Filament\Resources\Redirects\Pages\ManageRedirects;
use App\Modules\Core\Models\Redirect;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('removes a trailing slash with a permanent redirect and keeps the query', function () {
    $this->get('http://ezfin.test/cong-cu/?utm_source=zalo')
        ->assertStatus(301)
        ->assertRedirect('http://ezfin.test/cong-cu?utm_source=zalo');
});

it('sends an old url that no longer exists to its new address and counts the hit', function () {
    Redirect::create(['from_path' => 'https://ezfin.vn/Bai-Cu/', 'to_url' => '/cong-cu/lai-kep']);

    $this->get('http://ezfin.test/bai-cu?utm_source=fb')
        ->assertStatus(301)
        ->assertRedirect('/cong-cu/lai-kep?utm_source=fb');

    expect(Redirect::sole())
        ->from_path->toBe('/bai-cu')
        ->hits->toBe(1)
        ->last_hit_at->not->toBeNull();
});

it('never hides a page that exists, and ignores the app domain', function () {
    Redirect::create(['from_path' => '/cong-cu', 'to_url' => '/the-tin-dung', 'status_code' => 302]);
    Redirect::create(['from_path' => '/quan-tri-cu', 'to_url' => '/the-tin-dung']);

    $this->get('http://ezfin.test/cong-cu')->assertOk();
    $this->get('http://app.ezfin.test/quan-tri-cu')->assertNotFound();
});

it('rejects redirects that would loop or leave the site unsafely', function (array $data) {
    expect(fn () => Redirect::create($data))->toThrow(ValidationException::class);
})->with([
    'same url' => [['from_path' => '/a', 'to_url' => '/a/']],
    'home page' => [['from_path' => '/', 'to_url' => '/a']],
    'javascript' => [['from_path' => '/a', 'to_url' => 'javascript:alert(1)']],
    'protocol relative' => [['from_path' => '/a', 'to_url' => '//evil.example']],
    'plain http' => [['from_path' => '/a', 'to_url' => 'http://evil.example']],
    'bad status' => [['from_path' => '/a', 'to_url' => '/b', 'status_code' => 307]],
]);

it('collapses chains and drops a redirect that would hide the new url', function () {
    $redirects = app(RecordRedirect::class);

    $redirects->handle('/a', '/b');
    $redirects->handle('/b', '/c');

    expect(Redirect::orderBy('from_path')->pluck('to_url', 'from_path')->all())->toBe(['/a' => '/c', '/b' => '/c']);

    $redirects->handle('/c', '/a');

    expect(Redirect::where('from_path', '/a')->exists())->toBeFalse()
        ->and(Redirect::where('from_path', '/c')->value('to_url'))->toBe('/a');
});

it('keeps the old address of a published article when its slug or category changes', function () {
    $article = Article::factory()->published()->create(['slug' => 'lai-kep']);
    $oldUrl = $article->url();

    $article->update(['slug' => 'lai-kep-la-gi']);
    $this->get($oldUrl)->assertStatus(301)->assertRedirect('/kien-thuc/'.$article->category->slug.'/lai-kep-la-gi');

    $other = ArticleCategory::factory()->create(['slug' => 'tiet-kiem']);
    $article->update(['category_id' => $other->id]);
    $this->get($oldUrl)->assertRedirect('/kien-thuc/tiet-kiem/lai-kep-la-gi');

    $other->update(['slug' => 'gui-tiet-kiem']);
    $this->get($oldUrl)->assertRedirect('/kien-thuc/gui-tiet-kiem/lai-kep-la-gi');
    $this->get('http://ezfin.test/kien-thuc/tiet-kiem')->assertRedirect('/kien-thuc/gui-tiet-kiem');
    $this->get('http://ezfin.test/kien-thuc/gui-tiet-kiem/lai-kep-la-gi')->assertOk();
});

it('does not create redirects for drafts', function () {
    Article::factory()->create(['slug' => 'nhap'])->update(['slug' => 'nhap-moi']);

    expect(Redirect::count())->toBe(0);
});

it('keeps the old address of a published product when its slug changes', function () {
    $card = FinancialProduct::factory()->published()->create(['slug' => 'the-cu']);
    $card->creditCard()->create([]);

    $card->update(['slug' => 'the-moi']);

    $this->get('http://ezfin.test/the-tin-dung/the-cu')->assertStatus(301)->assertRedirect('/the-tin-dung/the-moi');
});

it('lets editors and super admins manage redirects in the admin', function (Role $role, int $status) {
    Filament::setCurrentPanel('admin');
    $this->actingAs(User::factory()->admin($role)->create());

    $this->get('http://app.ezfin.test/quan-tri/chuyen-huong')->assertStatus($status);
})->with([
    'super admin' => [Role::SuperAdmin, 200],
    'editor' => [Role::Editor, 200],
    'data operator' => [Role::DataOperator, 403],
]);

it('validates redirects created in the admin', function () {
    Filament::setCurrentPanel('admin');
    $this->actingAs(User::factory()->admin(Role::Editor)->create());
    Redirect::create(['from_path' => '/da-co', 'to_url' => '/b']);

    Livewire::test(ManageRedirects::class)
        ->callAction(CreateAction::class, ['from_path' => '/da-co/', 'to_url' => 'javascript:alert(1)', 'status_code' => 301])
        ->assertHasActionErrors(['from_path', 'to_url']);

    Livewire::test(ManageRedirects::class)
        ->callAction(CreateAction::class, ['from_path' => 'https://ezfin.vn/bai-cu', 'to_url' => '/kien-thuc', 'status_code' => 301])
        ->assertHasNoActionErrors();

    expect(Redirect::where('from_path', '/bai-cu')->value('to_url'))->toBe('/kien-thuc');
});
