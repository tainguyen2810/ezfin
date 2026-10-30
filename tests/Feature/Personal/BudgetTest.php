<?php

use App\Models\User;
use App\Modules\Personal\Actions\CreateDefaultCategories;
use App\Modules\Personal\Actions\RecordTransaction;
use App\Modules\Personal\Actions\RecordTransfer;
use App\Modules\Personal\Models\Budget;
use App\Modules\Personal\Models\Category;
use App\Modules\Personal\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;

uses(RefreshDatabase::class);

const BUDGETS = 'http://app.ezfin.test/ngan-sach';

beforeEach(function () {
    Feature::activate('personal');
    $this->user = User::factory()->create();
    app(CreateDefaultCategories::class)->handle($this->user);
    $this->actingAs($this->user);
    $this->wallet = Wallet::factory()->for($this->user)->create(['opening_balance' => 50_000_000]);
    $this->food = Category::where('name', 'Ăn uống')->sole();
    $this->transport = Category::where('name', 'Đi lại')->sole();
    $this->home = Category::where('name', 'Nhà ở')->sole();
});

function spend(Category $category, int $amount, string $date = '2026-10-10'): void
{
    app(RecordTransaction::class)->handle(['type' => 'expense', 'wallet_id' => test()->wallet->id, 'category_id' => $category->id,
        'amount' => $amount, 'occurred_on' => $date, 'note' => null]);
}

it('sets the month budget per category and removes empty ones', function () {
    $this->put(BUDGETS, ['thang' => '2026-10', 'amounts' => [
        $this->food->id => '7.000.000', $this->transport->id => '2000000', $this->home->id => '',
    ]])->assertRedirect(BUDGETS.'?thang=2026-10');

    expect(Budget::count())->toBe(2);

    $this->put(BUDGETS, ['thang' => '2026-10', 'amounts' => [$this->transport->id => '0']]);
    expect(Budget::pluck('category_id')->all())->toBe([$this->food->id]);
});

it('tracks spending against the budget with warning and overspend alerts', function () {
    foreach ([[$this->food, 7_000_000], [$this->transport, 2_000_000], [$this->home, 4_000_000]] as [$category, $amount]) {
        Budget::create(['category_id' => $category->id, 'month' => '2026-10-01', 'amount' => $amount]);
    }
    spend($this->food, 5_824_000);
    spend($this->transport, 2_100_000);
    spend($this->home, 1_000_000, '2026-09-30'); // tháng trước: không tính
    app(RecordTransfer::class)->handle(['from_wallet_id' => $this->wallet->id,
        'to_wallet_id' => Wallet::factory()->for($this->user)->create()->id, 'amount' => 9_000_000, 'occurred_on' => '2026-10-11', 'note' => null]);

    $this->get(BUDGETS.'?thang=2026-10')->assertOk()
        ->assertSee('Đã vượt ngân sách')->assertSee('Đi lại (vượt 100.000 đ)')
        ->assertSee('Sắp hết ngân sách')->assertSee('Ăn uống (đã dùng 83%)')
        ->assertSee('13.000.000 đ')   // tổng ngân sách
        ->assertSee('7.924.000 đ');   // đã chi
});

it('copies last month budgets into an empty month', function () {
    Budget::create(['category_id' => $this->food->id, 'month' => '2026-09-01', 'amount' => 6_000_000]);

    $this->get(BUDGETS.'?thang=2026-10')->assertSee('Dùng lại tháng trước');
    $this->post(BUDGETS.'/sao-chep', ['thang' => '2026-10'])->assertRedirect();

    expect(Budget::where('month', '2026-10-01')->sole()->amount)->toBe('6000000.00');
});

it('only budgets the user\'s own expense categories', function () {
    $stranger = User::factory()->create();
    app(CreateDefaultCategories::class)->handle($stranger);
    $theirs = Category::withoutGlobalScopes()->where('user_id', $stranger->id)->where('type', 'expense')->first();
    $income = Category::where('name', 'Lương')->sole();

    $this->put(BUDGETS, ['thang' => '2026-10', 'amounts' => [$theirs->id => '1000']])->assertStatus(422);
    $this->put(BUDGETS, ['thang' => '2026-10', 'amounts' => [$income->id => '1000']])->assertStatus(422);
    expect(Budget::withoutGlobalScopes()->count())->toBe(0);

    $budget = new Budget(['category_id' => $theirs->id, 'month' => '2026-10-01', 'amount' => 1]);
    $budget->user_id = $stranger->id;
    $budget->save();
    $this->get(BUDGETS.'?thang=2026-10')->assertSee('Chưa đặt ngân sách');
});
