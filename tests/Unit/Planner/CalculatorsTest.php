<?php

use App\Modules\Planner\Calculators\CalculationLimitExceeded;
use App\Modules\Planner\Calculators\CompoundInterest;
use App\Modules\Planner\Calculators\DepositInterest;
use App\Modules\Planner\Calculators\InterestMath;
use App\Modules\Planner\Calculators\RecurringSavings;
use App\Modules\Planner\Calculators\Repayment;
use App\Modules\Planner\Enums\Compounding;
use App\Modules\Planner\Enums\DepositPayout;
use App\Modules\Planner\Enums\RepaymentMethod;

/*
 * Số liệu kỳ vọng được tính tay (từng tháng, làm tròn nửa lên đến đồng) và đối chiếu với
 * hàm PMT của bảng tính. Không lấy kết quả từ chính code đang kiểm tra.
 */

it('parses rates exactly, with dot or comma', function (string $input, int $units) {
    expect(InterestMath::rateUnits($input))->toBe($units);
})->with([
    ['6', 60_000], ['6.5', 65_000], ['6,5', 65_000], ['0.0125', 125], ['10.25', 102_500], ['0', 0],
]);

it('rejects malformed rates', function (string $input) {
    expect(fn () => InterestMath::rateUnits($input))->toThrow(InvalidArgumentException::class);
})->with(['-1', 'abc', '6.12345', '1e3', '']);

it('rounds half up only once on accumulated interest', function () {
    // 1.000 đ × 6%/12 = 5 đ đúng; 1.001 đ → 5,005 đ → 5 đ; 100 đ × 1 tháng × 6% = 0,5 đ → 1 đ.
    expect(InterestMath::accrue(1_000, 60_000)->rounded())->toBe(5)
        ->and(InterestMath::accrue(1_001, 60_000)->rounded())->toBe(5)
        ->and(InterestMath::accrue(100, 60_000)->rounded())->toBe(1)
        // Ba lần 0,5 đ cộng dồn = 1,5 đ → 2 đ (làm tròn từng lần sẽ ra 3 đ).
        ->and(InterestMath::accrue(100, 60_000)->add(InterestMath::accrue(100, 60_000))->add(InterestMath::accrue(100, 60_000))->rounded())->toBe(2);
});

describe('compound interest', function () {
    it('compounds monthly', function () {
        // 100.000.000 đ, 12%/năm (1%/tháng), 12 tháng: 101.000.000 → 102.010.000 → 103.030.100 → 104.060.401
        // → 105.101.005 → 106.152.015 → 107.213.535 → 108.285.670 → 109.368.527 → 110.462.212 → 111.566.834 → 112.682.502.
        $result = CompoundInterest::calculate(100_000_000, 0, '12', 1, Compounding::Monthly);

        expect($result->balance)->toBe(112_682_502)
            ->and($result->interest)->toBe(12_682_502)
            ->and($result->contributed)->toBe(100_000_000);
    });

    it('compounds quarterly and yearly', function () {
        // 3%/quý: 103.000.000 → 106.090.000 → 109.272.700 → 112.550.881.
        expect(CompoundInterest::calculate(100_000_000, 0, '12', 1, Compounding::Quarterly)->balance)->toBe(112_550_881)
            // 12%/năm, 2 năm: 112.000.000 → 125.440.000.
            ->and(CompoundInterest::calculate(100_000_000, 0, '12', 2, Compounding::Yearly)->balance)->toBe(125_440_000)
            // 50.000.000 × 1,075^5 = 71.781.465,8 → làm tròn từng năm: 71.781.466.
            ->and(CompoundInterest::calculate(50_000_000, 0, '7.5', 5, Compounding::Yearly)->balance)->toBe(71_781_466);
    });

    it('adds monthly contributions at the end of each month', function () {
        // Góp 1.000.000 đ cuối mỗi tháng, 1%/tháng: lãi 0 → 10.000 → 20.100 → 30.301 → 40.604 → 51.010
        // → 61.520 → 72.135 → 82.857 → 93.685 → 104.622 → 115.668; tổng lãi 682.502 (công thức niên kim: 682.503,01).
        $result = CompoundInterest::calculate(0, 1_000_000, '12', 1, Compounding::Monthly);

        expect($result->balance)->toBe(12_682_502)
            ->and($result->contributed)->toBe(12_000_000)
            ->and($result->interest)->toBe(682_502);
    });

    it('reports each year', function () {
        $result = CompoundInterest::calculate(100_000_000, 0, '12', 2, Compounding::Yearly);

        expect($result->years)->toBe([
            ['year' => 1, 'contributed' => 100_000_000, 'interest' => 12_000_000, 'totalInterest' => 12_000_000, 'balance' => 112_000_000],
            ['year' => 2, 'contributed' => 100_000_000, 'interest' => 13_440_000, 'totalInterest' => 25_440_000, 'balance' => 125_440_000],
        ]);
    });

    it('stays flat at 0% and stops at the calculation limit', function () {
        expect(CompoundInterest::calculate(10_000_000, 1_000_000, '0', 2, Compounding::Monthly)->balance)->toBe(34_000_000)
            ->and(fn () => CompoundInterest::calculate(100_000_000_000, 0, '30', 50, Compounding::Monthly))->toThrow(CalculationLimitExceeded::class);
    });
});

describe('deposit interest', function () {
    it('pays simple interest at the end of the term', function () {
        // 50.000.000 × 4,75% × 7 / 12 = 1.385.416,67 → 1.385.417.
        $result = DepositInterest::calculate(50_000_000, '4.75', 7, DepositPayout::EndOfTerm);

        expect($result->interest)->toBe(1_385_417)
            ->and($result->total)->toBe(51_385_417)
            ->and($result->monthlyPayout)->toBeNull()
            ->and($result->months[6])->toBe(['month' => 7, 'interest' => 1_385_417, 'accrued' => 1_385_417])
            ->and($result->months[0]['interest'])->toBe(0);
    });

    it('pays interest every month', function () {
        // 100.000.000 × 6% / 12 = 500.000 mỗi tháng.
        $result = DepositInterest::calculate(100_000_000, '6', 12, DepositPayout::Monthly);

        expect($result->monthlyPayout)->toBe(500_000)
            ->and($result->interest)->toBe(6_000_000)
            ->and(array_column($result->months, 'interest'))->each->toBe(500_000);
    });
});

describe('recurring savings', function () {
    it('gives each monthly deposit interest until maturity', function () {
        // 1.000.000 đ đầu mỗi tháng, 6%/năm, 12 tháng: 5.000 × (12 + 11 + … + 1) = 5.000 × 78 = 390.000;
        // thêm 10.000.000 đ ban đầu × 6% = 600.000.
        $plain = RecurringSavings::calculate(1_000_000, '6', 12);
        $withInitial = RecurringSavings::calculate(1_000_000, '6', 12, 10_000_000);

        expect($plain->interest)->toBe(390_000)
            ->and($plain->deposited)->toBe(12_000_000)
            ->and($plain->total)->toBe(12_390_000)
            ->and($plain->months[1])->toBe(['month' => 2, 'deposited' => 2_000_000, 'interest' => 15_000, 'balance' => 2_015_000])
            ->and($withInitial->interest)->toBe(990_000)
            ->and($withInitial->total)->toBe(22_990_000);
    });
});

describe('repayment', function () {
    it('repays equal principal with interest on the remaining balance', function () {
        // 12.000.000 đ, 12%/năm, 12 tháng: gốc 1.000.000/tháng, lãi 120.000, 110.000, …, 10.000; tổng lãi 780.000.
        $schedule = Repayment::schedule(12_000_000, '12', 12, RepaymentMethod::EqualPrincipal);

        expect(array_column($schedule->months, 'interest'))->toBe(range(120_000, 10_000, -10_000))
            ->and($schedule->interest)->toBe(780_000)
            ->and($schedule->total)->toBe(12_780_000)
            ->and($schedule->firstPayment())->toBe(1_120_000)
            ->and($schedule->months[11]['payment'])->toBe(1_010_000)
            ->and($schedule->months[11]['balance'])->toBe(0);
    });

    it('puts the leftover principal in the last month', function () {
        $schedule = Repayment::schedule(10_000_000, '0', 3, RepaymentMethod::EqualPrincipal);

        expect(array_column($schedule->months, 'principal'))->toBe([3_333_333, 3_333_333, 3_333_334]);
    });

    it('repays an annuity with equal monthly payments', function () {
        // PMT(1%; 12; 12.000.000) = 1.066.185,46 → 1.066.185. Tháng 1: lãi 120.000, gốc 946.185, còn 11.053.815;
        // tháng 2: lãi 110.538, gốc 955.647. Tháng cuối trả hết dư nợ: 1.066.191; tổng lãi 794.226.
        $schedule = Repayment::schedule(12_000_000, '12', 12, RepaymentMethod::Annuity);

        expect($schedule->months[0])->toBe(['month' => 1, 'payment' => 1_066_185, 'principal' => 946_185, 'interest' => 120_000, 'balance' => 11_053_815])
            ->and($schedule->months[1])->toBe(['month' => 2, 'payment' => 1_066_185, 'principal' => 955_647, 'interest' => 110_538, 'balance' => 10_098_168])
            ->and($schedule->months[11]['payment'])->toBe(1_066_191)
            ->and($schedule->months[11]['balance'])->toBe(0)
            ->and($schedule->interest)->toBe(794_226)
            ->and($schedule->total)->toBe(12_794_226);
    });

    it('handles a 20-year mortgage', function () {
        // PMT(10,5%/12; 240; 500.000.000) = 4.991.899,…
        $schedule = Repayment::schedule(500_000_000, '10.5', 240, RepaymentMethod::Annuity);

        expect($schedule->firstPayment())->toBe(4_991_899)
            ->and(array_sum(array_column($schedule->months, 'principal')))->toBe(500_000_000)
            ->and($schedule->interest)->toBe(698_056_103)
            ->and($schedule->months[239]['balance'])->toBe(0);
    });

    it('charges flat interest on the original amount and shows the equivalent rate', function () {
        // Lãi phẳng 12%/năm trên 12.000.000 đ: 120.000/tháng × 12 = 1.440.000; trả 1.120.000/tháng.
        // Lãi suất dư nợ giảm dần tương đương ≈ 21,46%/năm (gần gấp đôi).
        $schedule = Repayment::schedule(12_000_000, '12', 12, RepaymentMethod::Flat);

        expect(array_unique(array_column($schedule->months, 'payment')))->toBe([1_120_000])
            ->and($schedule->interest)->toBe(1_440_000)
            ->and($schedule->effectiveRate)->toBe('21.46')
            ->and(Repayment::schedule(12_000_000, '12', 12, RepaymentMethod::EqualPrincipal)->effectiveRate)->toBeNull();
    });

    it('splits flat interest rounding into the last month', function () {
        // 10.000.000 × 10% × 7 / 12 = 583.333,33 → 583.333; 83.333 × 6 tháng + 83.335 tháng cuối.
        $schedule = Repayment::schedule(10_000_000, '10', 7, RepaymentMethod::Flat);

        expect($schedule->interest)->toBe(583_333)
            ->and(array_column($schedule->months, 'interest'))->toBe([83_333, 83_333, 83_333, 83_333, 83_333, 83_333, 83_335]);
    });

    it('repays without interest at 0%', function (RepaymentMethod $method) {
        $schedule = Repayment::schedule(12_000_000, '0', 12, $method);

        expect($schedule->interest)->toBe(0)
            ->and($schedule->total)->toBe(12_000_000)
            ->and($schedule->months[11]['balance'])->toBe(0)
            ->and($schedule->effectiveRate)->toBeNull();
    })->with(RepaymentMethod::cases());
});
