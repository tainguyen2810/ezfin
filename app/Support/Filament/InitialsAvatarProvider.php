<?php

namespace App\Support\Filament;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Ảnh đại diện chữ cái đầu, tạo tại chỗ. Thay cho ui-avatars.com mặc định của Filament
 * để không gửi tên người dùng ra dịch vụ bên ngoài.
 */
class InitialsAvatarProvider implements AvatarProvider
{
    public function get(Model $record): string
    {
        $initials = Str::of(Filament::getNameForDefaultAvatar($record))
            ->squish()
            ->explode(' ')
            ->filter()
            ->map(fn (string $word) => mb_strtoupper(mb_substr($word, 0, 1)))
            ->pipe(fn ($letters) => $letters->count() > 1 ? $letters->first().$letters->last() : $letters->first());

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">'
            .'<rect width="64" height="64" fill="#1e3a8a"/>'
            .'<text x="50%" y="50%" dy=".35em" text-anchor="middle" fill="#fff" font-family="Be Vietnam Pro, sans-serif" font-size="26" font-weight="600">'
            .e($initials ?: '?')
            .'</text></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
