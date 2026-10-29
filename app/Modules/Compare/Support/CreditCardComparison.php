<?php

namespace App\Modules\Compare\Support;

use App\Modules\Compare\Enums\CardBenefit;
use App\Modules\Compare\Models\FinancialProduct;
use App\Support\Format;
use Illuminate\Support\Collection;

/**
 * Dựng các dòng của bảng so sánh thẻ. "Tốt nhất" chỉ đánh dấu khi có ít nhất 2 thẻ biết giá trị
 * và giá trị tốt nhất không bị trùng ở mọi thẻ; tiêu chí là con số khách quan (phí thấp, hoàn tiền cao).
 */
final class CreditCardComparison
{
    /**
     * @param  Collection<int, FinancialProduct>  $cards
     * @return list<array{label: string, values: list<string>, best: list<int>, bestLabel: ?string}>
     */
    public static function rows(Collection $cards): array
    {
        $detail = fn (FinancialProduct $card) => $card->creditCard;

        $rows = [
            self::numeric('Phí thường niên', $cards, fn ($c) => $detail($c)->annual_fee, 'min', 'Thấp nhất',
                fn ($v, $c) => ((float) $v === 0.0 ? 'Miễn phí' : Format::money($v)).($detail($c)->annual_fee_note ? ' ('.$detail($c)->annual_fee_note.')' : '')),
            self::numeric('Phí phát hành', $cards, fn ($c) => $detail($c)->issuance_fee, 'min', 'Thấp nhất',
                fn ($v) => (float) $v === 0.0 ? 'Miễn phí' : Format::money($v)),
            self::numeric('Thu nhập tối thiểu', $cards, fn ($c) => $detail($c)->min_monthly_income, 'min', 'Thấp nhất',
                fn ($v) => Format::money($v).'/tháng'),
            self::numeric('Lãi suất', $cards, fn ($c) => $detail($c)->interest_rate, 'min', 'Thấp nhất', fn ($v) => Format::rate($v)),
            self::numeric('Miễn lãi tối đa', $cards, fn ($c) => $detail($c)->interest_free_days, 'max', 'Dài nhất', fn ($v) => $v.' ngày'),
            self::numeric('Hoàn tiền tối đa', $cards, fn ($c) => $detail($c)->cashback_rate, 'max', 'Cao nhất', fn ($v) => Format::percent($v)),
            self::numeric('Hoàn tiền tối đa/tháng', $cards, fn ($c) => $detail($c)->cashback_monthly_cap, 'max', 'Cao nhất', fn ($v) => Format::money($v)),
            self::numeric('Phí giao dịch ngoại tệ', $cards, fn ($c) => $detail($c)->foreign_transaction_fee, 'min', 'Thấp nhất', fn ($v) => Format::percent($v)),
            self::text('Tổ chức thẻ', $cards, fn ($c) => $detail($c)->network?->getLabel()),
            self::text('Nhóm lợi ích', $cards, fn ($c) => collect($detail($c)->benefits ?? [])
                ->map(fn ($benefit) => CardBenefit::tryFrom($benefit)?->getLabel())->filter()->implode(', ') ?: null),
            self::text('Ưu điểm', $cards, fn ($c) => $c->pros ? implode("\n", $c->pros) : null),
            self::text('Hạn chế', $cards, fn ($c) => $c->cons ? implode("\n", $c->cons) : null),
        ];

        return $rows;
    }

    /**
     * @param  callable(FinancialProduct): mixed  $value
     * @param  callable(mixed, FinancialProduct): string  $format
     */
    private static function numeric(string $label, Collection $cards, callable $value, string $direction, string $bestLabel, callable $format): array
    {
        $raw = $cards->map(fn ($card) => $value($card));
        $known = $raw->filter(fn ($v) => $v !== null && $v !== '')->map(fn ($v) => (float) $v);

        $best = [];
        if ($known->count() >= 2) {
            $target = $direction === 'min' ? $known->min() : $known->max();
            $best = $known->filter(fn ($v) => $v === $target)->keys()->all();
            // Mọi thẻ bằng nhau thì không có "tốt nhất".
            if (count($best) === $known->count()) {
                $best = [];
            }
        }

        return [
            'label' => $label,
            'values' => $raw->map(fn ($v, $i) => $v === null || $v === '' ? '—' : $format($v, $cards[$i]))->all(),
            'best' => array_values($best),
            'bestLabel' => $bestLabel,
        ];
    }

    /** @param  callable(FinancialProduct): ?string  $value */
    private static function text(string $label, Collection $cards, callable $value): array
    {
        return [
            'label' => $label,
            'values' => $cards->map(fn ($card) => $value($card) ?? '—')->all(),
            'best' => [],
            'bestLabel' => null,
        ];
    }
}
