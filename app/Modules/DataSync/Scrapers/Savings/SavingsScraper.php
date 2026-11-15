<?php

namespace App\Modules\DataSync\Scrapers\Savings;

use App\Modules\Compare\Actions\PublishSavingsRates;
use App\Modules\Compare\Enums\InterestPayment;
use App\Modules\Compare\Enums\SavingsChannel;
use App\Modules\DataSync\Contracts\Scraper;
use App\Modules\DataSync\Enums\SyncKind;
use App\Modules\DataSync\Models\SyncDraft;
use App\Modules\DataSync\Support\Banks;
use App\Modules\DataSync\Support\Fetcher;
use App\Modules\DataSync\Support\ScrapeException;
use App\Modules\DataSync\Support\ScrapeResult;
use Illuminate\Support\Carbon;

/**
 * Lãi suất tiết kiệm VND cho khách hàng cá nhân của một ngân hàng. Mỗi dòng: kỳ hạn (tháng), kênh, hình thức lĩnh lãi,
 * bậc số tiền, lãi suất %/năm. Bộ con chỉ cần đọc trang nguồn và gọi row().
 */
abstract class SavingsScraper implements Scraper
{
    /** Lãi suất VND hợp lý cho tiền gửi cá nhân (%/năm); ngoài khoảng này gần như chắc chắn là đọc sai cột. */
    public const MAX_RATE = 12;

    public const MAX_DEMAND_RATE = 2;

    public const MIN_ROWS = 4;

    /** Thay đổi quá mức này (điểm %) so với bản đã duyệt thì cảnh báo, không tự duyệt. */
    public const SUSPICIOUS_CHANGE = 2;

    /** @var list<array<string, mixed>> */
    private array $rows = [];

    /** Mã ngắn của ngân hàng trong key nguồn, ví dụ "vietcombank". */
    abstract protected function bank(): string;

    /**
     * Thông tin ngân hàng để tạo hồ sơ khi chưa có.
     *
     * @return array{slug: string, name: string, short_name: string, website: string}
     */
    public function institution(): array
    {
        return Banks::get($this->bank());
    }

    abstract protected function read(Fetcher $http): ?Carbon;

    public function key(): string
    {
        return 'savings.'.$this->bank();
    }

    public function kind(): SyncKind
    {
        return SyncKind::Savings;
    }

    public function name(): string
    {
        return $this->institution()['short_name'];
    }

    public function scrape(Fetcher $http): ScrapeResult
    {
        $this->rows = [];
        $effective = $this->read($http);

        if ($this->rows === []) {
            throw ScrapeException::layoutChanged('không tìm thấy dòng lãi suất nào.');
        }

        $rows = collect($this->rows)
            ->unique(fn ($row) => implode('|', [$row['term'], $row['channel'], $row['payment'], $row['min_amount']]))
            ->sortBy([['channel', 'asc'], ['payment', 'asc'], ['min_amount', 'asc'], ['term', 'asc']])
            ->values()->all();

        return new ScrapeResult(['rows' => $rows], $effective);
    }

    protected function row(int $term, SavingsChannel $channel, ?string $rate, InterestPayment $payment = InterestPayment::EndOfTerm, int $minAmount = 0, ?int $maxAmount = null): void
    {
        if ($rate === null) {
            return;
        }

        $this->rows[] = [
            'term' => $term,
            'channel' => $channel->value,
            'payment' => $payment->value,
            'min_amount' => $minAmount,
            'max_amount' => $maxAmount,
            'rate' => $rate,
        ];
    }

    public function validate(array $payload): array
    {
        $rows = $payload['rows'] ?? [];
        $errors = [];

        if (count($rows) < self::MIN_ROWS) {
            $errors[] = 'Chỉ đọc được '.count($rows).' dòng lãi suất (cần ít nhất '.self::MIN_ROWS.').';
        }

        foreach ($rows as $row) {
            $rate = (float) $row['rate'];
            $label = $this->label($row);
            if ($rate < 0 || $rate > self::MAX_RATE) {
                $errors[] = "Lãi suất {$label} = {$row['rate']}% nằm ngoài khoảng hợp lý 0–".self::MAX_RATE.'%.';
            } elseif ($row['term'] === 0 && $rate > self::MAX_DEMAND_RATE) {
                $errors[] = "Lãi suất không kỳ hạn = {$row['rate']}% cao bất thường.";
            }
        }

        $main = collect($rows)->filter(fn ($row) => in_array($row['term'], [6, 12], true));
        if ($rows !== [] && $main->isEmpty()) {
            $errors[] = 'Không có kỳ hạn 6 hoặc 12 tháng, bảng đọc được có thể không phải bảng lãi suất tiết kiệm.';
        }
        if ($main->isNotEmpty() && $main->every(fn ($row) => (float) $row['rate'] < 1)) {
            $errors[] = 'Mọi lãi suất kỳ hạn 6/12 tháng đều dưới 1%, gần như chắc chắn đọc sai cột.';
        }

        return array_values(array_unique($errors));
    }

    public function compare(array $payload, ?array $previous): array
    {
        if ($previous === null) {
            return [];
        }

        $old = collect($previous['rows'] ?? [])->keyBy(fn ($row) => $this->rowKey($row));
        $warnings = [];

        foreach ($payload['rows'] ?? [] as $row) {
            $before = $old->get($this->rowKey($row));
            if ($before && abs((float) $row['rate'] - (float) $before['rate']) > self::SUSPICIOUS_CHANGE) {
                $warnings[] = "Lãi suất {$this->label($row)} đổi từ {$before['rate']}% sang {$row['rate']}%, chênh hơn ".self::SUSPICIOUS_CHANGE.' điểm %.';
            }
        }

        $missing = $old->count() - collect($payload['rows'] ?? [])->filter(fn ($row) => $old->has($this->rowKey($row)))->count();
        if ($old->isNotEmpty() && $missing > $old->count() / 2) {
            $warnings[] = "Thiếu {$missing}/{$old->count()} dòng so với lần đã duyệt trước.";
        }

        return $warnings;
    }

    public function apply(SyncDraft $draft): string
    {
        $result = app(PublishSavingsRates::class)->handle(
            $this->institution(),
            $this->url(),
            $draft->rows(),
            $draft->effective_from,
        );

        return "Đã ghi {$result['rates']} mức lãi suất cho {$result['products']} sản phẩm, hiệu lực từ {$result['effective_from']->format('d/m/Y')}.";
    }

    private function rowKey(array $row): string
    {
        return implode('|', [$row['term'], $row['channel'], $row['payment'], $row['min_amount']]);
    }

    private function label(array $row): string
    {
        return implode(' ', array_filter([
            $row['term'] === 0 ? 'không kỳ hạn' : $row['term'].' tháng',
            SavingsChannel::from($row['channel'])->getLabel(),
            'lĩnh lãi '.mb_strtolower(InterestPayment::from($row['payment'])->getLabel()),
            $row['min_amount'] > 0 ? 'từ '.number_format($row['min_amount'], 0, ',', '.').' đ' : null,
        ]));
    }
}
