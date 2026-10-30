<?php

use App\Models\User;
use App\Modules\Personal\Actions\CashFlow;
use App\Modules\Personal\Actions\CreateDefaultCategories;
use App\Modules\Personal\Actions\RecordTransaction;
use App\Modules\Personal\Actions\RecordTransfer;
use App\Modules\Personal\Models\Budget;
use App\Modules\Personal\Models\Category;
use App\Modules\Personal\Models\Goal;
use App\Modules\Personal\Models\NetWorthItem;
use App\Modules\Personal\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Pennant\Feature;

uses(RefreshDatabase::class);

const DASHBOARD = 'http://app.ezfin.test/tong-quan';

beforeEach(function () {
    Feature::activate('personal');
    Carbon::setTestNow('2026-10-30 09:00');
    $this->user = User::factory()->create(['name' => 'Nguyễn Văn A']);
    app(CreateDefaultCategories::class)->handle($this->user);
    $this->actingAs($this->user);
    $this->wallet = Wallet::factory()->for($this->user)->create(['name' => 'Vietcombank', 'opening_balance' => 100_000_000]);
});

function record(string $type, string $category, int $amount, string $date, ?string $note = null): void
{
    app(RecordTransaction::class)->handle(['type' => $type, 'wallet_id' => test()->wallet->id,
        'category_id' => Category::where('name', $category)->value('id'), 'amount' => $amount, 'occurred_on' => $date, 'note' => $note]);
}

it('guides a new user to add a wallet first', function () {
    $this->wallet->delete();

    $this->get(DASHBOARD)->assertOk()->assertSee('Bắt đầu bằng ví đầu tiên')->assertDontSee('Dòng tiền 6 tháng');
});

it('shows totals, month figures and the change from last month', function () {
    record('income', 'Lương', 28_500_000, '2026-10-05', 'Lương tháng 10');
    record('expense', 'Ăn uống', 5_000_000, '2026-10-10');
    record('expense', 'Nhà ở', 3_000_000, '2026-10-12');
    record('income', 'Lương', 25_000_000, '2026-09-05');
    record('expense', 'Ăn uống', 10_000_000, '2026-09-10');
    NetWorthItem::create(['kind' => 'mortgage', 'name' => 'Vay mua nhà', 'amount' => 50_000_000, 'valued_on' => '2026-10-30']);

    $this->get(DASHBOARD)->assertOk()
        ->assertSee('Chào buổi sáng, Nguyễn Văn A!')
        ->assertSee('135.500.000 đ')          // tổng tài sản = số dư ví
        ->assertSee('50.000.000 đ')           // tổng nợ
        ->assertSee('28.500.000 đ')->assertSee('14% so với tháng trước')
        ->assertSee('8.000.000 đ')->assertSee('20% so với tháng trước')
        ->assertSee('Lương tháng 10')
        ->assertSee('Dòng tiền 6 tháng gần nhất')
        ->assertSee('Phân bổ chi tiêu tháng 10');
});

it('summarises monthly cash flow without transfers', function () {
    record('income', 'Lương', 20_000_000, '2026-04-30');   // ngoài 6 tháng
    record('income', 'Lương', 25_000_000, '2026-06-05');
    record('expense', 'Ăn uống', 4_000_000, '2026-10-01');
    app(RecordTransfer::class)->handle(['from_wallet_id' => $this->wallet->id,
        'to_wallet_id' => Wallet::factory()->for($this->user)->create()->id, 'amount' => 9_000_000, 'occurred_on' => '2026-10-02', 'note' => null]);

    $months = app(CashFlow::class)->monthly(Carbon::parse('2026-10-01'), 6);

    expect($months->map(fn ($m) => $m['month']->format('m'))->all())->toBe(['05', '06', '07', '08', '09', '10'])
        ->and($months->pluck('income')->all())->toBe([0, 25_000_000, 0, 0, 0, 0])
        ->and($months->last())->toMatchArray(['income' => 0, 'expense' => 4_000_000, 'net' => -4_000_000]);
});

it('groups spending by category with stable distinct colors', function () {
    foreach (['Ăn uống' => 6_000_000, 'Nhà ở' => 4_000_000, 'Học tập' => 3_000_000, 'Mua sắm' => 2_000_000,
        'Giải trí' => 1_000_000, 'Sức khỏe' => 1_000_000, 'Gia đình' => 2_000_000, 'Đi lại' => 1_000_000] as $name => $amount) {
        record('expense', $name, $amount, '2026-10-15');
    }

    $rows = app(CashFlow::class)->expenseByCategory(Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'));

    expect($rows)->toHaveCount(7)
        ->and($rows->first())->toMatchArray(['label' => 'Ăn uống', 'amount' => 6_000_000, 'percent' => 30, 'color' => CashFlow::PALETTE[0]])
        ->and($rows->last())->toMatchArray(['label' => 'Danh mục khác', 'amount' => 2_000_000, 'color' => CashFlow::OTHER_COLOR])
        ->and($rows->pluck('color')->unique())->toHaveCount(7);
});

it('shows goals and budgets, and hides months without data', function () {
    $goal = Goal::create(['name' => 'Mua nhà', 'target_amount' => 1_500_000_000, 'deadline' => '2028-12-31', 'icon' => 'home', 'color' => 'blue']);
    $goal->contributions()->create(['amount' => 300_000_000, 'contributed_on' => '2026-10-01']);
    Budget::create(['category_id' => Category::where('name', 'Ăn uống')->value('id'), 'month' => '2026-10-01', 'amount' => 7_000_000]);
    record('expense', 'Ăn uống', 5_824_000, '2026-10-03');

    $this->get(DASHBOARD)->assertOk()
        ->assertSee('Mua nhà')->assertSee('Đã có 300.000.000 đ · còn 26 tháng')
        ->assertSee('5.824.000 đ / 7.000.000 đ')
        ->assertDontSee('so với tháng trước'); // tháng 9 không có thu chi để so sánh
});

it('keeps other people\'s money out of the dashboard', function () {
    record('expense', 'Ăn uống', 5_000_000, '2026-10-10', 'Bữa tối bí mật');
    $this->actingAs($other = User::factory()->create());
    Wallet::factory()->for($other)->create(['opening_balance' => 1_000]);

    $this->get(DASHBOARD)->assertOk()->assertDontSee('Bữa tối bí mật')->assertDontSee('95.000.000 đ');
    expect(app(CashFlow::class)->totals(Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'))['expense'])->toBe(0);
});
