<?php

namespace App\Modules\Compare\Actions;

use App\Modules\Compare\Enums\CardBenefit;
use App\Modules\Compare\Enums\ProductType;
use App\Modules\Compare\Models\FinancialProduct;
use App\Modules\Compare\Support\CardQuizAnswers;
use App\Support\Format;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Gợi ý thẻ tín dụng từ câu trả lời quiz, theo tiêu chí công khai (xem trang Phương pháp đánh giá):
 * 1. Loại thẻ yêu cầu thu nhập cao hơn thu nhập người dùng (thẻ chưa công bố mức thu nhập vẫn giữ).
 * 2. Có tháng chỉ trả một phần: lãi suất thấp trước, vì tiền lãi lớn hơn mọi ưu đãi.
 * 3. Sau đó: khớp nhiều nhóm ưu tiên hơn, rồi lợi ích ròng ước tính (hoàn tiền một năm trừ phí thường niên) cao hơn,
 *    rồi (nếu chi tiêu ở nước ngoài) phí giao dịch ngoại tệ thấp hơn.
 * Không xét đối tác hay hoa hồng.
 */
class RecommendCreditCards
{
    public const LIMIT = 5;

    /**
     * @return array{cards: Collection<int, array{card: FinancialProduct, matches: list<CardBenefit>, cashback: ?int, net: ?int, reasons: list<string>}>, eligible: int, excluded: int}
     */
    public function handle(CardQuizAnswers $answers, int $limit = self::LIMIT): array
    {
        $cards = FinancialProduct::query()
            ->ofType(ProductType::CreditCard)
            ->published()
            ->whereHas('institution', fn (Builder $query) => $query->active())
            ->has('creditCard')
            ->with(['institution', 'creditCard'])
            ->get();

        $eligible = $cards->filter(fn (FinancialProduct $card) => $card->creditCard->min_monthly_income === null
            || (float) $card->creditCard->min_monthly_income <= $answers->income);

        $scored = $eligible->map(fn (FinancialProduct $card) => $this->score($card, $answers))
            ->sort(fn (array $a, array $b) => $this->sortKey($a, $answers) <=> $this->sortKey($b, $answers))
            ->values();

        $lowestRate = $scored->min(fn (array $item) => $item['card']->creditCard->interest_rate === null ? null : (float) $item['card']->creditCard->interest_rate);

        return [
            'cards' => $scored->take($limit)->map(fn (array $item) => [...$item, 'reasons' => $this->reasons($item, $answers, $lowestRate)])->values(),
            'eligible' => $eligible->count(),
            'excluded' => $cards->count() - $eligible->count(),
        ];
    }

    /** @return array{card: FinancialProduct, matches: list<CardBenefit>, cashback: ?int, net: ?int} */
    private function score(FinancialProduct $card, CardQuizAnswers $answers): array
    {
        $detail = $card->creditCard;
        $benefits = $detail->benefits ?? [];
        $matches = array_values(array_filter($answers->priorities, fn (CardBenefit $benefit) => in_array($benefit->value, $benefits, true)));

        $cashback = null;
        if ($detail->cashback_rate !== null) {
            $monthly = (int) round($answers->spend * (float) $detail->cashback_rate / 100);
            if ($detail->cashback_monthly_cap !== null) {
                $monthly = min($monthly, (int) $detail->cashback_monthly_cap);
            }
            $cashback = $monthly * 12;
        }

        $fee = $detail->annual_fee === null ? null : (int) $detail->annual_fee;

        return [
            'card' => $card,
            'matches' => $matches,
            'cashback' => $cashback,
            'net' => $fee === null ? null : ($cashback ?? 0) - $fee,
        ];
    }

    /** Khóa sắp xếp tăng dần; thông số chưa công bố xếp sau. */
    private function sortKey(array $item, CardQuizAnswers $answers): array
    {
        $detail = $item['card']->creditCard;
        $rate = $detail->interest_rate === null ? PHP_FLOAT_MAX : (float) $detail->interest_rate;
        $fx = $detail->foreign_transaction_fee === null ? PHP_FLOAT_MAX : (float) $detail->foreign_transaction_fee;

        return [
            $answers->carriesBalance() ? $rate : 0,
            -count($item['matches']),
            $item['net'] === null ? PHP_INT_MAX : -$item['net'],
            $answers->abroad ? $fx : 0,
            $item['card']->name,
        ];
    }

    /** @return list<string> */
    private function reasons(array $item, CardQuizAnswers $answers, ?float $lowestRate): array
    {
        $detail = $item['card']->creditCard;
        $reasons = [];

        if ($item['matches']) {
            $reasons[] = 'Hợp ưu tiên của bạn: '.collect($item['matches'])->map(fn (CardBenefit $benefit) => mb_strtolower($benefit->getLabel()))->join(', ', ' và ').'.';
        }

        if ($answers->carriesBalance() && $detail->interest_rate !== null) {
            $reasons[] = 'Lãi suất '.Format::rate($detail->interest_rate)
                .((float) $detail->interest_rate === $lowestRate ? ', thấp nhất trong các thẻ bạn đủ điều kiện.' : '.')
                .' Khi không trả hết dư nợ, tiền lãi thường lớn hơn mọi khoản hoàn tiền.';
        }

        if ($item['cashback'] !== null) {
            $reasons[] = 'Hoàn tiền ước tính '.Format::money($item['cashback']).'/năm với mức chi '.Format::money($answers->spend).'/tháng'
                .($detail->cashback_monthly_cap !== null ? ' (trần '.Format::money($detail->cashback_monthly_cap).'/tháng)' : '').'.';
        }

        if ($detail->annual_fee !== null) {
            $reasons[] = (float) $detail->annual_fee === 0.0
                ? 'Miễn phí thường niên.'
                : 'Phí thường niên '.Format::money($detail->annual_fee).($item['net'] !== null ? ', lợi ích ròng ước tính '.Format::money($item['net']).'/năm.' : '.');
        }

        if ($answers->abroad && $detail->foreign_transaction_fee !== null) {
            $reasons[] = 'Phí giao dịch ngoại tệ '.Format::percent($detail->foreign_transaction_fee).'.';
        }

        $reasons[] = $detail->min_monthly_income === null
            ? 'Ngân hàng chưa công bố thu nhập tối thiểu, hãy kiểm tra khi đăng ký.'
            : 'Thu nhập tối thiểu '.Format::money($detail->min_monthly_income).'/tháng: bạn đáp ứng.';

        return $reasons;
    }
}
