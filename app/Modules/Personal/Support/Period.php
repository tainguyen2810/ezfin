<?php

namespace App\Modules\Personal\Support;

use Illuminate\Support\Carbon;

/**
 * Kỳ báo cáo: tháng, quý hoặc năm. Trên URL: ?ky=thang&moc=2026-10, ?ky=quy&moc=2026-Q4, ?ky=nam&moc=2026.
 * Sai hoặc thiếu thì lấy kỳ đang diễn ra.
 */
final class Period
{
    public const KINDS = ['thang' => 'Tháng', 'quy' => 'Quý', 'nam' => 'Năm'];

    private function __construct(public readonly string $kind, public readonly Carbon $start) {}

    public static function fromQuery(mixed $kind, mixed $anchor): self
    {
        $kind = is_string($kind) && isset(self::KINDS[$kind]) ? $kind : 'thang';
        $anchor = is_string($anchor) ? $anchor : '';

        $start = match ($kind) {
            'thang' => preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $anchor, $m) ? Carbon::create((int) $m[1], (int) $m[2]) : null,
            'quy' => preg_match('/^(\d{4})-Q([1-4])$/', $anchor, $m) ? Carbon::create((int) $m[1], (int) $m[2] * 3 - 2) : null,
            'nam' => preg_match('/^(\d{4})$/', $anchor, $m) ? Carbon::create((int) $m[1]) : null,
        };

        return new self($kind, self::align($kind, $start ?? today()));
    }

    private static function align(string $kind, Carbon $date): Carbon
    {
        return match ($kind) {
            'thang' => $date->copy()->startOfMonth(),
            'quy' => $date->copy()->startOfQuarter(),
            'nam' => $date->copy()->startOfYear(),
        };
    }

    public function end(): Carbon
    {
        $end = match ($this->kind) {
            'thang' => $this->start->copy()->endOfMonth(),
            'quy' => $this->start->copy()->endOfQuarter(),
            'nam' => $this->start->copy()->endOfYear(),
        };

        return $end->startOfDay();
    }

    public function previous(): self
    {
        return new self($this->kind, self::align($this->kind, $this->start->copy()->subDay()));
    }

    public function next(): self
    {
        return new self($this->kind, $this->end()->addDay());
    }

    public function isCurrentOrFuture(): bool
    {
        return $this->end()->gte(today());
    }

    /** Giá trị cho ?moc= */
    public function anchor(): string
    {
        return match ($this->kind) {
            'thang' => $this->start->format('Y-m'),
            'quy' => $this->start->format('Y').'-Q'.$this->start->quarter,
            'nam' => $this->start->format('Y'),
        };
    }

    public function label(): string
    {
        return match ($this->kind) {
            'thang' => 'Tháng '.$this->start->format('m/Y'),
            'quy' => 'Quý '.$this->start->quarter.'/'.$this->start->format('Y'),
            'nam' => 'Năm '.$this->start->format('Y'),
        };
    }

    /** Số tháng trên biểu đồ, kết thúc ở tháng cuối của kỳ: xem tháng thì kèm 5 tháng trước, quý 3 tháng, năm 12 tháng. */
    public function chartMonths(): int
    {
        return ['thang' => 6, 'quy' => 3, 'nam' => 12][$this->kind];
    }

    /** @return array{ky: string, moc: string} */
    public function query(): array
    {
        return ['ky' => $this->kind, 'moc' => $this->anchor()];
    }
}
