<?php

use App\Models\User;
use App\Modules\Personal\Actions\CreateDefaultCategories;
use App\Modules\Personal\Enums\TransactionType;
use App\Modules\Personal\Models\Category;
use App\Modules\Personal\Models\Transaction;
use App\Modules\Personal\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;

uses(RefreshDatabase::class);

const TX = 'http://app.ezfin.test/giao-dich';

beforeEach(function () {
    Feature::activate('personal');
    $this->user = User::factory()->create();
    app(CreateDefaultCategories::class)->handle($this->user);
    $this->actingAs($this->user);
    $this->cash = Wallet::factory()->for($this->user)->create(['name' => 'Tiền mặt', 'opening_balance' => 1_000_000]);
    $this->bank = Wallet::factory()->for($this->user)->create(['name' => 'Vietcombank', 'opening_balance' => 10_000_000]);
    $this->food = Category::where('name', 'Ăn uống')->sole();
    $this->salary = Category::where('name', 'Lương')->sole();
});

function expense(array $overrides = []): array
{
    return ['type' => 'expense', 'amount' => '120.000', 'wallet_id' => test()->cash->id, 'category_id' => test()->food->id,
        'occurred_on' => '2026-10-05', 'note' => 'Ăn trưa', ...$overrides];
}

it('records income and expenses and keeps wallet balances right', function () {
    $this->post(TX, expense())->assertRedirect(TX.'?thang=2026-10');
    $this->post(TX, ['type' => 'income', 'amount' => '15000000', 'wallet_id' => $this->bank->id, 'category_id' => $this->salary->id, 'occurred_on' => '2026-10-01']);

    expect($this->cash->fresh()->balance())->toBe(880_000)
        ->and($this->bank->fresh()->balance())->toBe(25_000_000);

    $this->get(TX.'?thang=2026-10')->assertOk()
        ->assertSee('+15.000.000 đ')->assertSee('−120.000 đ')->assertSee('Ăn trưa')
        ->assertSee('+14.880.000 đ');
});

it('moves money between wallets as two linked rows that are not income or expense', function () {
    $this->post(TX, ['type' => 'transfer', 'amount' => '2.000.000', 'from_wallet_id' => $this->bank->id,
        'to_wallet_id' => $this->cash->id, 'occurred_on' => '2026-10-10'])->assertRedirect();

    $legs = Transaction::all();
    expect($legs)->toHaveCount(2)
        ->and($legs->pluck('transfer_group_id')->unique())->toHaveCount(1)
        ->and($legs->pluck('type')->all())->toEqualCanonicalizing([TransactionType::TransferOut, TransactionType::TransferIn])
        ->and($this->bank->fresh()->balance())->toBe(8_000_000)
        ->and($this->cash->fresh()->balance())->toBe(3_000_000);

    $this->get(TX.'?thang=2026-10')->assertSee('Thu nhập')->assertSeeInOrder(['Chênh lệch', '+0 đ']);

    // Xóa một vế là xóa cả lần chuyển.
    $this->delete(TX.'/'.$legs->first()->id)->assertRedirect();
    expect(Transaction::count())->toBe(0)->and($this->bank->fresh()->balance())->toBe(10_000_000);
});

it('rejects a transfer to the same wallet', function () {
    $this->post(TX, ['type' => 'transfer', 'amount' => '1000', 'from_wallet_id' => $this->cash->id,
        'to_wallet_id' => $this->cash->id, 'occurred_on' => '2026-10-10'])->assertSessionHasErrors('to_wallet_id');
});

it('edits a transfer into an expense and back', function () {
    $this->post(TX, ['type' => 'transfer', 'amount' => '500000', 'from_wallet_id' => $this->bank->id,
        'to_wallet_id' => $this->cash->id, 'occurred_on' => '2026-10-10']);
    $out = Transaction::where('type', TransactionType::TransferOut)->sole();

    $this->put(TX.'/'.$out->id, expense(['wallet_id' => $this->bank->id, 'amount' => '500000']))->assertRedirect();
    expect(Transaction::count())->toBe(1)->and(Transaction::sole()->type)->toBe(TransactionType::Expense);

    $this->put(TX.'/'.$out->id, ['type' => 'transfer', 'amount' => '700000', 'from_wallet_id' => $this->bank->id,
        'to_wallet_id' => $this->cash->id, 'occurred_on' => '2026-10-11'])->assertRedirect();
    expect(Transaction::count())->toBe(2)->and($this->cash->fresh()->balance())->toBe(1_700_000);
});

it('requires a category of the matching type', function () {
    $this->post(TX, expense(['category_id' => $this->salary->id]))->assertSessionHasErrors('category_id');
    $this->post(TX, expense(['amount' => '0']))->assertSessionHasErrors('amount');
});

it('filters by month, wallet, category and kind', function () {
    $this->post(TX, expense(['note' => 'Tháng mười']));
    $this->post(TX, expense(['note' => 'Tháng chín', 'occurred_on' => '2026-09-20']));
    $this->post(TX, expense(['note' => 'Ví ngân hàng', 'wallet_id' => $this->bank->id]));

    $this->get(TX.'?thang=2026-10')->assertSee('Tháng mười')->assertDontSee('Tháng chín');
    $this->get(TX.'?thang=2026-10&vi='.$this->bank->id)->assertSee('Ví ngân hàng')->assertDontSee('Tháng mười');
    $this->get(TX.'?thang=2026-10&loai=thu')->assertDontSee('Tháng mười');
    $this->get(TX.'?thang=2026-10&danh_muc='.$this->food->id)->assertSee('Tháng mười');
});

it('never uses or shows another user\'s wallets, categories or transactions', function () {
    $stranger = User::factory()->create();
    app(CreateDefaultCategories::class)->handle($stranger);
    $theirWallet = Wallet::factory()->for($stranger)->create();
    $theirCategory = Category::withoutGlobalScopes()->where('user_id', $stranger->id)->where('type', 'expense')->first();
    $theirTx = new Transaction(['wallet_id' => $theirWallet->id, 'category_id' => $theirCategory->id, 'type' => 'expense',
        'amount' => 99_000, 'occurred_on' => '2026-10-05', 'note' => 'Bí mật']);
    $theirTx->user_id = $stranger->id;
    $theirTx->save();

    $this->post(TX, expense(['wallet_id' => $theirWallet->id]))->assertSessionHasErrors('wallet_id');
    $this->post(TX, expense(['category_id' => $theirCategory->id]))->assertSessionHasErrors('category_id');
    $this->get(TX.'?thang=2026-10')->assertDontSee('Bí mật');
    $this->get(TX.'/'.$theirTx->id.'/edit')->assertNotFound();
    $this->delete(TX.'/'.$theirTx->id)->assertNotFound();
    $this->get('http://app.ezfin.test/danh-muc/'.$theirCategory->id.'/edit')->assertNotFound();
});

it('asks for a wallet before the first transaction and creates default categories', function () {
    $fresh = User::factory()->create();
    $this->actingAs($fresh)->get(TX.'/create')->assertRedirect('http://app.ezfin.test/vi/create');

    Wallet::factory()->for($fresh)->create();
    $this->get(TX.'/create')->assertOk()->assertSee('Ăn uống');
    $this->get(TX.'/create?loai=thu')->assertOk()->assertSee('Lương')->assertDontSee('Ăn uống');
});

it('manages categories', function () {
    $this->post('http://app.ezfin.test/danh-muc', ['type' => 'expense', 'name' => 'Thú cưng', 'icon' => 'heart', 'color' => 'pink'])->assertRedirect();
    $pet = Category::where('name', 'Thú cưng')->sole();

    $this->put('http://app.ezfin.test/danh-muc/'.$pet->id, ['name' => 'Chó mèo', 'icon' => 'face-smile', 'color' => 'amber'])->assertRedirect();
    expect($pet->fresh()->name)->toBe('Chó mèo');

    $this->post('http://app.ezfin.test/danh-muc', ['type' => 'expense', 'name' => 'X', 'icon' => '../etc', 'color' => 'pink'])->assertSessionHasErrors('icon');

    $this->delete('http://app.ezfin.test/danh-muc/'.$pet->id)->assertRedirect();
    $this->get('http://app.ezfin.test/danh-muc')->assertSee('Đã ẩn danh mục'); // thông báo chỉ hiện một lần
    $this->get(TX.'/create')->assertDontSee('Chó mèo');
});
