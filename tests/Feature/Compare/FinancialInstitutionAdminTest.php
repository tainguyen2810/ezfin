<?php

use App\Models\User;
use App\Modules\Compare\Enums\InstitutionType;
use App\Modules\Compare\Filament\Resources\FinancialInstitutions\Pages\CreateFinancialInstitution;
use App\Modules\Compare\Filament\Resources\FinancialInstitutions\Pages\EditFinancialInstitution;
use App\Modules\Compare\Filament\Resources\FinancialInstitutions\Pages\ListFinancialInstitutions;
use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\AuditLog;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

const INSTITUTIONS_URL = 'http://app.ezfin.test/quan-tri/to-chuc-tai-chinh';

beforeEach(function () {
    Storage::fake('public');
    Filament::setCurrentPanel('admin');
});

function validInstitution(array $overrides = []): array
{
    return [
        'type' => InstitutionType::Bank->value,
        'short_name' => 'Ngân hàng Đông Á',
        'name' => 'Ngân hàng TMCP Thử nghiệm Đông Á',
        'slug' => 'ngan-hang-dong-a',
        'website' => 'https://example.com',
        'is_active' => true,
        ...$overrides,
    ];
}

it('lets data operators and super admins manage, editors only view', function (Role $role, int $list, int $create) {
    $this->actingAs(User::factory()->admin($role)->create());

    $this->get(INSTITUTIONS_URL)->assertStatus($list);
    $this->get(INSTITUTIONS_URL.'/create')->assertStatus($create);
})->with([
    'super admin' => [Role::SuperAdmin, 200, 200],
    'data operator' => [Role::DataOperator, 200, 200],
    'editor' => [Role::Editor, 200, 403],
    'finance' => [Role::Finance, 403, 403],
]);

it('only lets super admins delete', function () {
    $institution = FinancialInstitution::factory()->create();

    expect(User::factory()->admin(Role::DataOperator)->create()->can('delete', $institution))->toBeFalse()
        ->and(User::factory()->admin()->create()->can('delete', $institution))->toBeTrue();
});

it('creates an institution with a logo and writes the audit log', function () {
    $this->actingAs($admin = User::factory()->admin(Role::DataOperator)->create());

    Livewire::test(CreateFinancialInstitution::class)
        ->fillForm(validInstitution(['logo_path' => UploadedFile::fake()->image('logo.png', 200, 200)]))
        ->call('create')
        ->assertHasNoFormErrors();

    $institution = FinancialInstitution::sole();

    expect($institution->slug)->toBe('ngan-hang-dong-a')
        ->and($institution->type)->toBe(InstitutionType::Bank)
        ->and($institution->logo_path)->toStartWith('institutions/')
        ->and(AuditLog::where('event', 'created')->where('auditable_id', $institution->id)->where('user_id', $admin->id)->exists())->toBeTrue();

    Storage::disk('public')->assertExists($institution->logo_path);
});

it('suggests a slug without Vietnamese accents from the short name', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(CreateFinancialInstitution::class)
        ->fillForm(['short_name' => 'Đầu tư & Phát triển'])
        ->assertSchemaStateSet(['slug' => 'dau-tu-phat-trien']);
});

it('validates required fields and formats', function (array $data, array $errors) {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(CreateFinancialInstitution::class)
        ->fillForm(validInstitution($data))
        ->call('create')
        ->assertHasFormErrors($errors);

    expect(FinancialInstitution::count())->toBe(0);
})->with([
    'missing names' => [['short_name' => '', 'name' => ''], ['short_name' => 'required', 'name' => 'required']],
    'missing type' => [['type' => null], ['type' => 'required']],
    'slug with accents' => [['slug' => 'ngân-hàng'], ['slug' => 'regex']],
    'slug with spaces' => [['slug' => 'ngan hang'], ['slug' => 'regex']],
    'website not https' => [['website' => 'http://example.com'], ['website']],
    'website not a url' => [['website' => 'khong-phai-url'], ['website']],
]);

it('rejects a duplicate slug but keeps the current one when editing', function () {
    $this->actingAs(User::factory()->admin()->create());
    $existing = FinancialInstitution::factory()->create(['slug' => 'ngan-hang-dong-a']);

    Livewire::test(CreateFinancialInstitution::class)
        ->fillForm(validInstitution())
        ->call('create')
        ->assertHasFormErrors(['slug' => 'unique']);

    Livewire::test(EditFinancialInstitution::class, ['record' => $existing->getRouteKey()])
        ->fillForm(['name' => 'Tên mới'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($existing->fresh()->name)->toBe('Tên mới');
});

it('rejects logos that are not images or too large', function (UploadedFile $file) {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(CreateFinancialInstitution::class)
        ->fillForm(validInstitution(['logo_path' => $file]))
        ->call('create')
        ->assertHasFormErrors(['logo_path']);
})->with([
    'pdf' => fn () => UploadedFile::fake()->create('logo.pdf', 10, 'application/pdf'),
    'svg' => fn () => UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
    'over 1 MB' => fn () => UploadedFile::fake()->image('logo.png')->size(1500),
]);

it('removes the old logo file when the logo is replaced or the institution is deleted', function () {
    Storage::disk('public')->put('institutions/old.png', 'x');
    Storage::disk('public')->put('institutions/new.png', 'x');
    $institution = FinancialInstitution::factory()->create(['logo_path' => 'institutions/old.png']);

    $institution->update(['logo_path' => 'institutions/new.png']);
    Storage::disk('public')->assertMissing('institutions/old.png');

    $institution->delete();
    Storage::disk('public')->assertMissing('institutions/new.png');
});

it('lists, searches and deletes institutions with an audit trail', function () {
    $this->actingAs(User::factory()->admin()->create());
    [$a, $b] = FinancialInstitution::factory()->count(2)->sequence(
        ['short_name' => 'Ngân hàng Alpha', 'slug' => 'alpha'],
        ['short_name' => 'Công ty Beta', 'slug' => 'beta', 'type' => InstitutionType::FinanceCompany],
    )->create();

    Livewire::test(ListFinancialInstitutions::class)
        ->assertCanSeeTableRecords([$a, $b])
        ->searchTable('Alpha')
        ->assertCanSeeTableRecords([$a])
        ->assertCanNotSeeTableRecords([$b]);

    Livewire::test(EditFinancialInstitution::class, ['record' => $b->getRouteKey()])
        ->callAction(DeleteAction::class);

    expect(FinancialInstitution::find($b->id))->toBeNull()
        ->and(AuditLog::where('event', 'deleted')->where('auditable_id', $b->id)->exists())->toBeTrue();
});

it('serves logos from a same-origin path on both domains', function () {
    $institution = FinancialInstitution::factory()->make(['logo_path' => 'institutions/logo.png']);

    expect(config('filesystems.disks.public.url'))->toBe('/storage')
        ->and($institution->logoUrl())->toBe('/storage/institutions/logo.png');
});
