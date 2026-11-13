<?php

namespace App\Modules\Partner\Actions;

use App\Modules\Partner\Enums\ConversionStatus;
use App\Modules\Partner\Models\AffiliateConversion;
use App\Modules\Partner\Models\OutboundClick;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

/**
 * Ghi hoặc cập nhật một chuyển đổi từ postback hay file đối soát.
 * Khóa chống trùng: (mạng, mã giao dịch); không có mã giao dịch thì dùng mã click (một chuyển đổi mỗi click).
 * Khớp mã click với nhật ký /di/ để biết link, sản phẩm và trang nguồn.
 */
class RecordConversion
{
    public const NETWORK_PATTERN = '/^[a-z0-9][a-z0-9_-]{0,49}$/';

    /**
     * @param  array{click_id?: ?string, transaction_id?: ?string, status?: ?string, commission?: string|int|float|null, occurred_at?: ?string}  $data
     * @return array{conversion: AffiliateConversion, created: bool}
     *
     * @throws InvalidArgumentException dữ liệu không hợp lệ (thông báo tiếng Việt)
     */
    public function handle(string $network, array $data): array
    {
        $network = Str::lower(trim($network));
        if (! preg_match(self::NETWORK_PATTERN, $network)) {
            throw new InvalidArgumentException('Tên mạng affiliate chỉ gồm chữ thường không dấu, số, "-" và "_".');
        }

        $clickId = $this->clean($data['click_id'] ?? null);
        if ($clickId !== null && ! preg_match('/^[A-Za-z0-9]{24}$/', $clickId)) {
            throw new InvalidArgumentException('Mã click không hợp lệ.');
        }

        $externalId = $this->clean($data['transaction_id'] ?? null) ?? $clickId;
        if ($externalId === null) {
            throw new InvalidArgumentException('Thiếu mã giao dịch hoặc mã click.');
        }
        if (mb_strlen($externalId) > 100) {
            throw new InvalidArgumentException('Mã giao dịch dài quá 100 ký tự.');
        }

        $status = ConversionStatus::fromPartner($data['status'] ?? null)
            ?? throw new InvalidArgumentException('Trạng thái "'.Str::limit((string) $data['status'], 30).'" không hợp lệ (dùng pending, approved hoặc rejected).');

        $commission = $this->money($data['commission'] ?? null);
        $occurredAt = $this->date($data['occurred_at'] ?? null);

        return DB::transaction(function () use ($network, $externalId, $clickId, $status, $commission, $occurredAt) {
            $conversion = AffiliateConversion::query()->lockForUpdate()
                ->where('network', $network)->where('external_id', $externalId)->first()
                ?? new AffiliateConversion(['network' => $network, 'external_id' => $externalId, 'occurred_at' => $occurredAt ?? now()]);
            $created = ! $conversion->exists;

            if ($clickId !== null && $conversion->click_id === null) {
                $click = OutboundClick::query()->where('click_id', $clickId)->first(['link_id', 'referer_path']);
                $conversion->fill(['click_id' => $clickId, 'link_id' => $click?->link_id, 'source_path' => $click?->referer_path]);
            }

            $conversion->status = $status;
            if ($commission !== null) {
                $conversion->commission = $commission;
            }
            if ($occurredAt !== null) {
                $conversion->occurred_at = $occurredAt;
            }
            $conversion->save();

            return ['conversion' => $conversion, 'created' => $created];
        });
    }

    private function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /** Hoa hồng VND: số nguyên hoặc có tối đa 2 chữ số thập phân sau dấu chấm. Không đoán dấu phân cách hàng nghìn. */
    private function money(string|int|float|null $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        if (! preg_match('/^\d{1,15}(\.\d{1,2})?$/', $value)) {
            throw new InvalidArgumentException('Hoa hồng "'.Str::limit($value, 20).'" không hợp lệ (chỉ ghi số, ví dụ 150000 hoặc 150000.50).');
        }

        return $value;
    }

    private function date(?string $value): ?Carbon
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        foreach (['d/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y', 'Y-m-d H:i:s', 'Y-m-d\TH:i:sP', 'Y-m-d'] as $format) {
            try {
                $date = Carbon::createFromFormat('!'.$format, $value);
            } catch (Throwable) {
                continue;
            }
            if ($date !== null && $date->format($format) === $value) {
                return $date->isFuture() ? now() : $date;
            }
        }

        if (ctype_digit($value) && strlen($value) === 10) {
            return Carbon::createFromTimestamp((int) $value, config('app.timezone'))->min(now());
        }

        throw new InvalidArgumentException('Ngày "'.Str::limit($value, 25).'" không hợp lệ (dùng dd/mm/yyyy hoặc yyyy-mm-dd).');
    }
}
