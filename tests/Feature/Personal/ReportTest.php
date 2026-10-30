<?php

use App\Models\User;
use App\Modules\Personal\Actions\CreateDefaultCategories;
use App\Modules\Personal\Actions\RecordTransaction;
use App\Modules\Personal\Models\Category;
use App\Modules\Personal\Models\Wallet;
use App\Modules\Personal\Support\Period;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Pennant\Feature;

uses(RefreshDatabase::class);

const REPORTS = 'http://app.ezfin.test/bao-cao';

beforeEach(function () {
    Feature::activate('personal');
    Carbon::setTestNow('2026-10-30 09:00');
    $this->user = User::factory()->create();
    app(CreateDefaultCategories::class)->handle($this->user);
    $this->actingAs($this->user);
    $this->wallet = Wallet::factory()->for($this->user)->create(['opening_balance' => 0]);
});

function ghi(string $type, string $category, int $amount, string $date): void
{
    app(RecordTransaction::class)->handle(['type' => $type, 'wallet_id' => test()->wallet->id,
        'category_id' => Category::where('name', $category)->value('id'), 'amount' => $amount, 'occurred_on' => $date, 'note' => null]);
}

it('parses report periods from the query string', function (?string $kind, ?string $anchor, string $label, string $start, string $end) {
    $period = Period::fromQuery($kind, $anchor);

    expect($period->label())->toBe($label)
        ->and($period->start->toDateString())->toBe($start)
        ->and($period->end()->toDateString())->toBe($end);
})->with([
    'mặc định' => [null, null, 'Tháng 10/2026', '2026-10-01', '2026-10-31'],
    'tháng' => ['thang', '2026-02', 'Tháng 02/2026', '2026-02-01', '2026-02-28'],
    'quý' => ['quy', '2026-Q3', 'Quý 3/2026', '2026-07-01', '2026-09-30'],
    'năm' => ['nam', '2025', 'Năm 2025', '2025-01-01', '2025-12-31'],
    'sai định dạng' => ['quy', '2026-13', 'Quý 4/2026', '2026-10-01', '2026-12-31'],
    'kỳ lạ' => ['tuan', 'x', 'Tháng 10/2026', '2026-10-01', '2026-10-31'],
]);

it('moves to the previous and next period', function () {
    expect(Period::fromQuery('quy', '2026-Q1')->previous()->anchor())->toBe('2025-Q4')
        ->and(Period::fromQuery('thang', '2026-12')->next()->anchor())->toBe('2027-01')
        ->and(Period::fromQuery('nam', '2026')->next()->query())->toBe(['ky' => 'nam', 'moc' => '2027']);
});

it('reports income, expense and categories for a month', function () {
    ghi('income', 'Lương', 28_500_000, '2026-10-05');
    ghi('income', 'Thưởng', 1_500_000, '2026-10-20');
    ghi('expense', 'Ăn uống', 6_000_000, '2026-10-10');
    ghi('expense', 'Nhà ở', 4_000_000, '2026-10-11');
    ghi('expense', 'Ăn uống', 8_000_000, '2026-09-10');
    ghi('expense', 'Ăn uống', 99_000_000, '2026-11-01'); // ngoài kỳ

    $this->get(REPORTS)->assertOk()
        ->assertSee('Tháng 10/2026')
        ->assertSee('30.000.000 đ')     // thu nhập
        ->assertSee('10.000.000 đ')     // chi tiêu
        ->assertSee('20.000.000 đ')     // còn lại
        ->assertSee('↑ 25% so với tháng trước')
        ->assertSee('Tỷ lệ để dành: <strong class="num text-navy-900">67%</strong>', false)
        ->assertSee('Chi tiêu theo danh mục')->assertSee('60%')
        ->assertDontSee('99.000.000 đ');
});

it('reports a quarter and a year', function () {
    ghi('expense', 'Ăn uống', 1_000_000, '2026-07-15');
    ghi('expense', 'Ăn uống', 2_000_000, '2026-09-15');
    ghi('expense', 'Ăn uống', 4_000_000, '2026-02-15');

    $this->get(REPORTS.'?ky=quy&moc=2026-Q3')->assertOk()->assertSee('Quý 3/2026')->assertSee('3.000.000 đ')->assertDontSee('7.000.000 đ');
    $this->get(REPORTS.'?ky=nam&moc=2026')->assertOk()->assertSee('Năm 2026')->assertSee('7.000.000 đ')->assertSee('T12');
});

it('shows an empty state for a period without data', function () {
    $this->get(REPORTS.'?ky=thang&moc=2020-01')->assertOk()->assertSee('Chưa có thu chi trong kỳ này');
});

it('only reports the signed-in user\'s money', function () {
    ghi('expense', 'Ăn uống', 12_345_000, '2026-10-10');
    $this->actingAs(User::factory()->create());

    $this->get(REPORTS)->assertOk()->assertDontSee('12.345.000 đ')->assertSee('Chưa có thu chi trong kỳ này');
});
