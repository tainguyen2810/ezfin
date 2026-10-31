<?php

namespace App\Modules\Personal\Imports;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Bảng sao kê đã đọc, giữ tạm trên ổ riêng tư (local, không công khai) giữa bước tải lên và bước nhập.
 * Mỗi người một thư mục; xóa sau khi nhập, khi tải file mới (bản quá 1 ngày) và khi xóa tài khoản.
 */
class StatementUploads
{
    private const DIRECTORY = 'statement-imports';

    /** @param  array{name: string, wallet_id: int, rows: list<list<mixed>>}  $data */
    public function put(int $userId, array $data): string
    {
        $this->purgeOld($userId);
        $token = (string) Str::uuid();
        Storage::disk('local')->put($this->path($userId, $token), json_encode($data, JSON_UNESCAPED_UNICODE));

        return $token;
    }

    /** @return array{name: string, wallet_id: int, rows: list<list<mixed>>}|null */
    public function get(int $userId, string $token): ?array
    {
        if (! Str::isUuid($token) || ! Storage::disk('local')->exists($this->path($userId, $token))) {
            return null;
        }

        return json_decode((string) Storage::disk('local')->get($this->path($userId, $token)), true) ?: null;
    }

    public function forget(int $userId, string $token): void
    {
        if (Str::isUuid($token)) {
            Storage::disk('local')->delete($this->path($userId, $token));
        }
    }

    public function forgetUser(int $userId): void
    {
        Storage::disk('local')->deleteDirectory(self::DIRECTORY.'/'.$userId);
    }

    private function purgeOld(int $userId): void
    {
        $disk = Storage::disk('local');
        foreach ($disk->files(self::DIRECTORY.'/'.$userId) as $file) {
            if ($disk->lastModified($file) < now()->subDay()->getTimestamp()) {
                $disk->delete($file);
            }
        }
    }

    private function path(int $userId, string $token): string
    {
        return self::DIRECTORY.'/'.$userId.'/'.$token.'.json';
    }
}
