<?php

namespace App\Modules\Compare\Actions;

use App\Modules\Compare\Enums\SavingsChannel;
use App\Modules\Compare\Models\FinancialInstitution;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Lãi suất tiết kiệm cao nhất theo kỳ hạn, dùng cho trang đích "Lãi suất tiết kiệm X tháng cao nhất"
 * và cho module khác (bản tin, báo lãi suất tăng). Số liệu lấy từ SavingsRateBoard: biểu lãi mới nhất
 * đã hiệu lực, bậc tiền thấp nhất, chỉ sản phẩm đã xuất bản của ngân hàng còn hoạt động.
 */
class BestSavingsRates
{
    /** Kỳ hạn có trang đích riêng. */
    public const TERMS = [1, 3, 6, 9, 12, 13, 18, 24, 36];

    /** So sánh với mức lãi của bao nhiêu ngày trước để hiện "tăng/giảm". */
    public const CHANGE_DAYS = 30;

    public function __construct(private readonly SavingsRateBoard $board) {}

    /**
     * Mỗi ngân hàng một dòng, xếp theo mức cao nhất (online hoặc tại quầy) giảm dần.
     *
     * @return Collection<int, array{institution: FinancialInstitution, online: ?float, counter: ?float, best: float, previous: ?float}>
     */
    public function forTerm(int $term, ?CarbonInterface $at = null): Collection
    {
        $at ??= today();
        $byChannel = fn (?SavingsChannel $channel, CarbonInterface $date) => $this->board->matrix($channel, $date)
            ->mapWithKeys(fn (array $row) => [$row['institution']->id => $row['rates'][$term] ?? null]);

        $online = $byChannel(SavingsChannel::Online, $at);
        $counter = $byChannel(SavingsChannel::Counter, $at);
        $previous = $byChannel(null, $at->copy()->subDays(self::CHANGE_DAYS));

        return $this->board->matrix(null, $at)
            ->filter(fn (array $row) => ($row['rates'][$term] ?? null) !== null)
            ->map(fn (array $row) => [
                'institution' => $row['institution'],
                'online' => $online->get($row['institution']->id),
                'counter' => $counter->get($row['institution']->id),
                'best' => $row['rates'][$term],
                'previous' => $previous->get($row['institution']->id),
            ])
            ->sort(fn (array $a, array $b) => [$b['best'], $a['institution']->short_name ?? $a['institution']->name]
                <=> [$a['best'], $b['institution']->short_name ?? $b['institution']->name])
            ->values();
    }

    /**
     * Mức cao nhất thị trường của mỗi kỳ hạn (cho bản tin và báo lãi suất tăng).
     *
     * @param  list<int>|null  $terms
     * @return array<int, array{rate: float, institution: FinancialInstitution}> kỳ hạn => mức cao nhất; bỏ kỳ hạn chưa có dữ liệu
     */
    public function topByTerm(?array $terms = null, ?CarbonInterface $at = null): array
    {
        $rows = $this->board->matrix(null, $at);
        $result = [];

        foreach ($terms ?? self::TERMS as $term) {
            $best = $rows->filter(fn (array $row) => ($row['rates'][$term] ?? null) !== null)
                ->sortByDesc(fn (array $row) => $row['rates'][$term])
                ->first();

            if ($best) {
                $result[$term] = ['rate' => $best['rates'][$term], 'institution' => $best['institution']];
            }
        }

        return $result;
    }

    /** @return list<int> kỳ hạn có trang đích và đang có dữ liệu */
    public function availableTerms(): array
    {
        return array_keys($this->topByTerm());
    }
}
