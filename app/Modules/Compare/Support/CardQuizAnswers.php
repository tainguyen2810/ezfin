<?php

namespace App\Modules\Compare\Support;

use App\Modules\Compare\Enums\CardBenefit;
use Illuminate\Http\Request;

/**
 * Câu trả lời quiz gợi ý thẻ, đọc từ query string (form GET, không lưu). Giá trị lạ bị bỏ qua.
 * Chỉ có kết quả khi đủ thu nhập, mức chi tiêu và cách trả nợ.
 */
final readonly class CardQuizAnswers
{
    public const MAX_MONEY = 10_000_000_000;

    public const REPAYMENT = [
        'het' => 'Trả hết dư nợ mỗi tháng',
        'mot-phan' => 'Có tháng chỉ trả một phần',
    ];

    public const QUERY_KEYS = ['thu_nhap', 'chi_tieu', 'uu_tien', 'tra_no', 'nuoc_ngoai'];

    /** @param  list<CardBenefit>  $priorities */
    public function __construct(
        public ?int $income = null,
        public ?int $spend = null,
        public array $priorities = [],
        public ?string $repayment = null,
        public bool $abroad = false,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $money = function (string $key) use ($request): ?int {
            $digits = is_string($value = $request->query($key)) ? preg_replace('/\D/', '', $value) : '';

            return $digits !== '' && strlen($digits) <= 11 && (int) $digits > 0 && (int) $digits <= self::MAX_MONEY ? (int) $digits : null;
        };
        $priorities = is_array($value = $request->query('uu_tien')) ? $value : [];
        $repayment = is_string($value = $request->query('tra_no')) ? $value : '';

        return new self(
            income: $money('thu_nhap'),
            spend: $money('chi_tieu'),
            priorities: collect($priorities)->filter(fn ($slug) => is_string($slug))
                ->map(fn (string $slug) => CardBenefit::fromSlug($slug))->filter()->unique()->values()->all(),
            repayment: array_key_exists($repayment, self::REPAYMENT) ? $repayment : null,
            abroad: $request->query('nuoc_ngoai') === 'co',
        );
    }

    public function isComplete(): bool
    {
        return $this->income !== null && $this->spend !== null && $this->repayment !== null;
    }

    public function carriesBalance(): bool
    {
        return $this->repayment === 'mot-phan';
    }

    /** @return list<string> */
    public function prioritySlugs(): array
    {
        return array_map(fn (CardBenefit $benefit) => $benefit->slug(), $this->priorities);
    }
}
