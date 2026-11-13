<?php

use App\Modules\Compare\Actions\SavingsRateBoard;
use App\Modules\Compare\Enums\InterestPayment;
use App\Modules\Compare\Enums\ProductStatus;
use App\Modules\Compare\Enums\SavingsChannel;
use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Compare\Models\FinancialProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Pennant\Feature;

uses(RefreshDatabase::class);

/** Sản phẩm tiết kiệm với các dòng [kỳ hạn, kênh, lãi, ngày hiệu lực, số tiền tối thiểu]. */
function marketSavings(FinancialInstitution $institution, array $rates, array $product = []): FinancialProduct
{
    $model = FinancialProduct::factory()->savings()->published()->create(['institution_id' => $institution->id, ...$product]);
    foreach ($rates as [$term, $channel, $rate, $from, $min]) {
        $model->savingsRates()->forceCreate([
            'term_months' => $term, 'channel' => $channel->value, 'interest_payment' => InterestPayment::EndOfTerm->value,
            'rate' => $rate, 'min_amount' => $min, 'effective_from' => $from,
        ]);
    }

    return $model;
}

beforeEach(function () {
    Carbon::setTestNow('2026-11-13 09:00:00');
    Feature::activate('compare');

    $alpha = FinancialInstitution::factory()->create(['short_name' => 'Alpha']);
    $beta = FinancialInstitution::factory()->create(['short_name' => 'Beta']);
    marketSavings($alpha, [
        [12, SavingsChannel::Online, 5.0, '2026-06-10', 0],
        [12, SavingsChannel::Online, 7.0, '2026-06-10', 1_000_000_000], // bậc tiền lớn: không dùng
        [12, SavingsChannel::Online, 5.5, '2026-09-05', 0],
        [12, SavingsChannel::Online, 9.0, '2026-12-01', 0],             // chưa hiệu lực
        [6, SavingsChannel::Online, 4.0, '2026-06-10', 0],              // kỳ hạn khác, bị biểu lãi 05/09 (không có kỳ hạn 6) thay thế
    ]);
    marketSavings($beta, [
        [12, SavingsChannel::Online, 5.2, '2026-08-20', 0],
        [12, SavingsChannel::Counter, 6.0, '2026-08-20', 0],            // mức cao nhất mọi kênh
    ]);
    marketSavings($beta, [[12, SavingsChannel::Online, 9.9, '2026-01-01', 0]], ['status' => ProductStatus::Draft]);
});

afterEach(fn () => Carbon::setTestNow());

it('builds the month-end best and average rate across banks without inventing months', function () {
    $history = app(SavingsRateBoard::class)->marketHistory(12);

    expect(array_column($history, 'month'))->toBe(['06/2026', '07/2026', '08/2026', '09/2026', '10/2026', '11/2026'])
        ->and(array_column($history, 'best'))->toBe([5.0, 5.0, 6.0, 6.0, 6.0, 6.0])
        ->and(array_column($history, 'average'))->toBe([5.0, 5.0, 5.5, 5.75, 5.75, 5.75])
        ->and(array_column($history, 'banks'))->toBe([1, 1, 2, 2, 2, 2])
        ->and($history[5]['date'])->toBe('2026-11-13')
        ->and($history[0]['date'])->toBe('2026-06-30');
});

it('shows the chart with a data table on the term landing page', function () {
    $this->get('http://ezfin.test/lai-suat-tiet-kiem/12-thang')->assertOk()
        ->assertSee('Lãi suất 12 tháng 12 tháng qua')
        ->assertSee('Xem bảng số liệu')
        ->assertSeeInOrder(['06/2026', '5%', '5%', '1'])
        ->assertSee('5,75%')
        ->assertDontSee('9,9%');
});

it('hides the chart when there is only one month of data', function () {
    // Kỳ hạn 6 tháng chỉ có tới khi Alpha công bố biểu lãi mới (05/09) không còn kỳ hạn này.
    expect(array_column(app(SavingsRateBoard::class)->marketHistory(6), 'month'))->toBe(['06/2026', '07/2026', '08/2026']);

    Carbon::setTestNow('2026-06-15 09:00:00');
    $this->get('http://ezfin.test/lai-suat-tiet-kiem/12-thang')->assertOk()->assertDontSee('12 tháng qua');
});
