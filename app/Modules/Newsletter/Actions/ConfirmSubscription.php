<?php

namespace App\Modules\Newsletter\Actions;

use App\Modules\Compare\Actions\BestSavingsRates;
use App\Modules\Newsletter\Models\Subscriber;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Bước 2 của double opt-in: người dùng bấm nút xác nhận trên trang mở từ email.
 * Tạo (hoặc bổ sung lựa chọn cho) người nhận; mức lãi hiện tại làm mốc để chỉ báo khi lãi tăng lên.
 */
class ConfirmSubscription
{
    /**
     * Đọc link xác nhận. null khi link sai hoặc hết hạn.
     *
     * @return ?array{email: string, term: ?int, weekly: bool, source: ?string, consent_at: Carbon}
     */
    public function read(string $payload): ?array
    {
        try {
            $data = json_decode(Crypt::decryptString($payload), true, flags: JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException) {
            return null;
        }

        if (! is_array($data) || ! filter_var($data['e'] ?? null, FILTER_VALIDATE_EMAIL) || ($data['x'] ?? 0) < now()->timestamp) {
            return null;
        }

        $term = isset($data['t']) && in_array((int) $data['t'], BestSavingsRates::TERMS, true) ? (int) $data['t'] : null;

        return [
            'email' => (string) $data['e'],
            'term' => $term,
            'weekly' => (bool) ($data['w'] ?? false),
            'source' => $data['s'] ?? null,
            'consent_at' => Carbon::createFromTimestamp((int) ($data['c'] ?? now()->timestamp), config('app.timezone')),
        ];
    }

    /** @param  array{email: string, term: ?int, weekly: bool, source: ?string, consent_at: Carbon}  $request */
    public function handle(array $request): Subscriber
    {
        return DB::transaction(function () use ($request) {
            $subscriber = Subscriber::query()->lockForUpdate()->where('email', $request['email'])->first()
                ?? Subscriber::create([
                    'email' => $request['email'],
                    'source_path' => $request['source'],
                    'consent_at' => $request['consent_at'],
                    'confirmed_at' => now(),
                ]);

            if ($request['weekly'] && ! $subscriber->weekly_digest) {
                $subscriber->update(['weekly_digest' => true]);
            }

            if ($request['term'] !== null) {
                $subscriber->alerts()->firstOrCreate(
                    ['term_months' => $request['term']],
                    ['baseline_rate' => app(BestSavingsRates::class)->topByTerm([$request['term']])[$request['term']]['rate'] ?? null],
                );
            }

            return $subscriber;
        });
    }
}
