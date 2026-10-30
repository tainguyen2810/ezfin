<?php

use App\Models\User;
use App\Modules\Core\Models\AuditLog;
use App\Modules\Personal\Actions\CreateDefaultCategories;
use App\Modules\Personal\Actions\RecordTransaction;
use App\Modules\Personal\Models\Category;
use App\Modules\Personal\Models\Goal;
use App\Modules\Personal\Models\Transaction;
use App\Modules\Personal\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Pennant\Feature;

uses(RefreshDatabase::class);

const ACCOUNT = 'http://app.ezfin.test/tai-khoan';

beforeEach(function () {
    Feature::activate('personal');
    $this->user = User::factory()->create(['name' => 'Nguyễn Văn A', 'email' => 'a@example.com', 'password' => 'matkhau123']);
    $this->actingAs($this->user);
});

it('shows the account page with every app section for mobile', function () {
    $this->get(ACCOUNT)->assertOk()
        ->assertSee('Thông tin cá nhân')->assertSee('a@example.com')
        ->assertSee('Tất cả mục')->assertSee('Mục tiêu')->assertSee('Tài sản &amp; nợ', false)
        ->assertSee('Tải giao dịch (CSV)')->assertSee('Xóa tài khoản');
});

it('updates profile and marketing consent', function () {
    $this->put(ACCOUNT, ['name' => 'Nguyễn Văn B', 'email' => 'b@example.com', 'marketing' => '1'])->assertRedirect();
    $user = $this->user->fresh();
    expect($user->name)->toBe('Nguyễn Văn B')->and($user->email)->toBe('b@example.com')->and($user->marketing_consent_at)->not->toBeNull();

    $this->put(ACCOUNT, ['name' => 'Nguyễn Văn B', 'email' => 'b@example.com']);
    expect($this->user->fresh()->marketing_consent_at)->toBeNull();
});

it('rejects an email used by someone else', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->put(ACCOUNT, ['name' => 'A', 'email' => 'taken@example.com'])->assertSessionHasErrors('email');
});

it('does not write customer self-service to the admin audit log', function () {
    $this->put(ACCOUNT, ['name' => 'Tên mới', 'email' => 'a@example.com']);

    expect(AuditLog::whereIn('event', ['created', 'updated', 'deleted'])->count())->toBe(0);
});

it('changes the password only with the current one', function () {
    $this->put(ACCOUNT.'/mat-khau', ['current_password' => 'sai', 'password' => 'matkhaumoi1', 'password_confirmation' => 'matkhaumoi1'])
        ->assertSessionHasErrors('current_password');

    $this->put(ACCOUNT.'/mat-khau', ['current_password' => 'matkhau123', 'password' => 'matkhaumoi1', 'password_confirmation' => 'matkhaumoi1'])
        ->assertSessionHasNoErrors();
    expect(Hash::check('matkhaumoi1', $this->user->fresh()->password))->toBeTrue();
});

it('exports only the user\'s transactions as CSV', function () {
    app(CreateDefaultCategories::class)->handle($this->user);
    $wallet = Wallet::factory()->for($this->user)->create(['name' => 'Tiền mặt']);
    app(RecordTransaction::class)->handle(['type' => 'expense', 'wallet_id' => $wallet->id, 'category_id' => Category::where('name', 'Ăn uống')->value('id'),
        'amount' => 120_000, 'occurred_on' => '2026-10-05', 'note' => '=HYPERLINK("x")']);
    $other = User::factory()->create();
    (new Transaction)->forceFill(['user_id' => $other->id, 'wallet_id' => Wallet::factory()->for($other)->create()->id,
        'type' => 'income', 'amount' => 999_999, 'occurred_on' => '2026-10-05', 'note' => 'của người khác'])->save();

    $csv = $this->get(ACCOUNT.'/xuat-giao-dich.csv')->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8')
        ->streamedContent();

    expect($csv)->toStartWith("\xEF\xBB\xBFNgày,Loại,\"Danh mục\",Ví,\"Số tiền\",\"Ghi chú\"")
        ->toContain('05/10/2026,"Chi tiêu","Ăn uống","Tiền mặt",-120000,"\'=HYPERLINK(""x"")"')
        ->not->toContain('999999')->not->toContain('của người khác');
});

it('deletes the account and all personal data after confirming the password', function () {
    $wallet = Wallet::factory()->for($this->user)->create();
    Goal::create(['name' => 'Mua nhà', 'target_amount' => 1_000, 'icon' => 'home', 'color' => 'blue']);
    $other = User::factory()->create();
    Wallet::factory()->for($other)->create();

    $this->delete(ACCOUNT, ['delete_password' => 'sai'])->assertSessionHasErrors('delete_password');
    expect(User::find($this->user->id))->not->toBeNull();

    $this->delete(ACCOUNT, ['delete_password' => 'matkhau123'])->assertRedirect(route('personal.login'));

    $this->assertGuest();
    expect(User::find($this->user->id))->toBeNull()
        ->and(Wallet::withoutGlobalScopes()->where('user_id', $this->user->id)->exists())->toBeFalse()
        ->and(Goal::withoutGlobalScopes()->count())->toBe(0)
        ->and(Wallet::withoutGlobalScopes()->where('user_id', $other->id)->count())->toBe(1);
});

it('does not let an admin delete their own account here', function () {
    $admin = User::factory()->admin()->create(['password' => 'matkhau123']);
    $this->actingAs($admin);

    $this->get(ACCOUNT)->assertOk()->assertSee('tài khoản quản trị nên không tự xóa');
    $this->delete(ACCOUNT, ['delete_password' => 'matkhau123'])->assertForbidden();
    expect(User::find($admin->id))->not->toBeNull();
});

it('links the avatar to the account page', function () {
    $this->get('http://app.ezfin.test/tong-quan')->assertSee('href="'.route('personal.settings.edit').'"', false);
});
