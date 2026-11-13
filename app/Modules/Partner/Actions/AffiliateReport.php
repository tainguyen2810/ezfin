<?php

namespace App\Modules\Partner\Actions;

use App\Modules\Partner\Enums\ConversionStatus;
use App\Modules\Partner\Models\AffiliateConversion;
use App\Modules\Partner\Models\OutboundClick;
use App\Modules\Partner\Models\OutboundLink;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Báo cáo hiệu quả đối tác trong một khoảng ngày: click (nhật ký /di/, đã lọc bot), chuyển đổi và hoa hồng
 * (theo ngày phát sinh). "Chuyển đổi" không tính dòng bị từ chối. Tiền: số nguyên đồng.
 */
class AffiliateReport
{
    public const UNKNOWN = 'Không rõ';

    /**
     * @return array{
     *     totals: array{clicks: int, conversions: int, rate: ?float, approved: int, pending: int, rejected: int},
     *     byInstitution: Collection<int, array<string, mixed>>,
     *     byLink: Collection<int, array<string, mixed>>,
     *     bySource: Collection<int, array<string, mixed>>,
     *     byDay: Collection<string, array{clicks: int, conversions: int}>,
     * }
     */
    public function handle(CarbonInterface $from, CarbonInterface $to): array
    {
        $start = $from->copy()->startOfDay();
        $end = $to->copy()->endOfDay();

        // Gộp click ngay trong SQL (nhật ký có thể rất lớn); mỗi dòng = một nhóm link × trang nguồn × ngày.
        $clicks = OutboundClick::query()->toBase()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('link_id, referer_path as path, DATE(created_at) as day, COUNT(*) as n')
            ->groupBy('link_id', 'referer_path', DB::raw('DATE(created_at)'))
            ->get()
            ->map(fn ($row) => (object) ['link_id' => (int) $row->link_id, 'path' => $row->path, 'day' => (string) $row->day, 'n' => (int) $row->n]);
        $conversions = AffiliateConversion::query()->whereBetween('occurred_at', [$start, $end])
            ->get(['link_id', 'source_path', 'status', 'commission', 'occurred_at'])
            ->map(fn (AffiliateConversion $conversion) => (object) [
                'link_id' => $conversion->link_id, 'path' => $conversion->source_path, 'day' => $conversion->occurred_at->toDateString(),
                'status' => $conversion->status, 'commission' => (float) $conversion->commission,
            ]);

        $linkIds = $clicks->pluck('link_id')->merge($conversions->pluck('link_id'))->filter()->unique()->values();
        $links = OutboundLink::query()->whereIn('id', $linkIds)
            ->with('product:id,name,institution_id', 'product.institution:id,name,short_name')
            ->get(['id', 'name', 'partner', 'product_id'])
            ->keyBy('id');

        $institutionOf = function (?int $linkId) use ($links): string {
            $institution = $links->get($linkId)?->product?->institution;

            return $institution ? ($institution->short_name ?: $institution->name) : self::UNKNOWN;
        };

        return [
            'totals' => $this->row($clicks, $conversions),
            'byInstitution' => $this->group($clicks, $conversions, fn ($item) => $institutionOf($item->link_id))
                ->map(fn (array $row, string $key) => ['label' => $key, ...$row])->values(),
            'byLink' => $this->group($clicks, $conversions, fn ($item) => (string) ($item->link_id ?? 0))
                ->map(function (array $row, string $key) use ($links, $institutionOf) {
                    $link = $links->get((int) $key);

                    return [
                        'label' => $link?->name ?? 'Chưa khớp link (mã click không có trong nhật ký)',
                        'product' => $link?->product?->name,
                        'institution' => $link ? $institutionOf($link->id) : null,
                        ...$row,
                    ];
                })->values(),
            'bySource' => $this->group($clicks, $conversions, fn ($item) => (string) ($item->path ?? ''))
                ->map(fn (array $row, string $key) => ['label' => $key === '' ? self::UNKNOWN : $key, ...$row])->values(),
            'byDay' => collect(iterator_to_array($start->toPeriod($end->copy()->startOfDay())))
                ->mapWithKeys(fn (CarbonInterface $day) => [$day->toDateString() => [
                    'clicks' => (int) $clicks->where('day', $day->toDateString())->sum('n'),
                    'conversions' => $conversions->where('day', $day->toDateString())->where('status', '!=', ConversionStatus::Rejected)->count(),
                ]]),
        ];
    }

    /** @return Collection<string, array{clicks: int, conversions: int, rate: ?float, approved: int, pending: int, rejected: int}> xếp theo click rồi hoa hồng */
    private function group(Collection $clicks, Collection $conversions, callable $key): Collection
    {
        $clickGroups = $clicks->groupBy($key);
        $conversionGroups = $conversions->groupBy($key);

        return $clickGroups->keys()->merge($conversionGroups->keys())->unique()
            ->mapWithKeys(fn ($group) => [(string) $group => $this->row($clickGroups->get($group, collect()), $conversionGroups->get($group, collect()))])
            ->sortByDesc(fn (array $row) => [$row['approved'] + $row['pending'], $row['conversions'], $row['clicks']]);
    }

    /** @return array{clicks: int, conversions: int, rate: ?float, approved: int, pending: int, rejected: int} */
    private function row(Collection $clicks, Collection $conversions): array
    {
        $sum = fn (ConversionStatus $status) => (int) round($conversions->where('status', $status)->sum('commission'));
        $counted = $conversions->where('status', '!=', ConversionStatus::Rejected)->count();
        $clickCount = (int) $clicks->sum('n');

        return [
            'clicks' => $clickCount,
            'conversions' => $counted,
            'rate' => $clickCount > 0 ? round($counted / $clickCount * 100, 2) : null,
            'approved' => $sum(ConversionStatus::Approved),
            'pending' => $sum(ConversionStatus::Pending),
            'rejected' => $sum(ConversionStatus::Rejected),
        ];
    }
}
