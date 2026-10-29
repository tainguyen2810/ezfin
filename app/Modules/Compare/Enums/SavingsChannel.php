<?php

namespace App\Modules\Compare\Enums;

use Filament\Support\Contracts\HasLabel;

/** Kênh gửi tiết kiệm: lãi suất online thường cao hơn tại quầy. */
enum SavingsChannel: string implements HasLabel
{
    case Online = 'online';
    case Counter = 'counter';

    /** Giá trị trong file CSV và trên URL công khai. */
    public function slug(): string
    {
        return match ($this) {
            self::Online => 'online',
            self::Counter => 'tai-quay',
        };
    }

    public static function fromSlug(?string $slug): ?self
    {
        return collect(self::cases())->first(fn (self $case) => $case->slug() === $slug);
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Online => 'Online',
            self::Counter => 'Tại quầy',
        };
    }
}
