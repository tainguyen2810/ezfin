<?php

namespace App\Modules\DataSync\Support;

/** Thông tin chính thức các ngân hàng EzFin theo dõi (tên pháp lý, tên ngắn, website). Dùng cho hồ sơ ngân hàng và bộ lấy lãi suất. */
final class Banks
{
    /** @var array<string, array{slug: string, name: string, short_name: string, website: string}> */
    public const ALL = [
        'vietcombank' => ['slug' => 'vietcombank', 'name' => 'Ngân hàng TMCP Ngoại thương Việt Nam', 'short_name' => 'Vietcombank', 'website' => 'https://www.vietcombank.com.vn'],
        'bidv' => ['slug' => 'bidv', 'name' => 'Ngân hàng TMCP Đầu tư và Phát triển Việt Nam', 'short_name' => 'BIDV', 'website' => 'https://bidv.com.vn'],
        'vietinbank' => ['slug' => 'vietinbank', 'name' => 'Ngân hàng TMCP Công Thương Việt Nam', 'short_name' => 'VietinBank', 'website' => 'https://www.vietinbank.vn'],
        'agribank' => ['slug' => 'agribank', 'name' => 'Ngân hàng Nông nghiệp và Phát triển Nông thôn Việt Nam', 'short_name' => 'Agribank', 'website' => 'https://www.agribank.com.vn'],
        'techcombank' => ['slug' => 'techcombank', 'name' => 'Ngân hàng TMCP Kỹ Thương Việt Nam', 'short_name' => 'Techcombank', 'website' => 'https://techcombank.com'],
        'mb' => ['slug' => 'mb', 'name' => 'Ngân hàng TMCP Quân đội', 'short_name' => 'MB', 'website' => 'https://www.mbbank.com.vn'],
        'acb' => ['slug' => 'acb', 'name' => 'Ngân hàng TMCP Á Châu', 'short_name' => 'ACB', 'website' => 'https://acb.com.vn'],
        'vpbank' => ['slug' => 'vpbank', 'name' => 'Ngân hàng TMCP Việt Nam Thịnh Vượng', 'short_name' => 'VPBank', 'website' => 'https://www.vpbank.com.vn'],
        'sacombank' => ['slug' => 'sacombank', 'name' => 'Ngân hàng TMCP Sài Gòn Thương Tín', 'short_name' => 'Sacombank', 'website' => 'https://www.sacombank.com.vn'],
        'tpbank' => ['slug' => 'tpbank', 'name' => 'Ngân hàng TMCP Tiên Phong', 'short_name' => 'TPBank', 'website' => 'https://tpb.vn'],
    ];

    /** @return array{slug: string, name: string, short_name: string, website: string} */
    public static function get(string $slug): array
    {
        return self::ALL[$slug];
    }
}
