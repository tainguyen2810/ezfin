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
        'hdbank' => ['slug' => 'hdbank', 'name' => 'Ngân hàng TMCP Phát triển Thành phố Hồ Chí Minh', 'short_name' => 'HDBank', 'website' => 'https://hdbank.com.vn'],
        'vib' => ['slug' => 'vib', 'name' => 'Ngân hàng TMCP Quốc tế Việt Nam', 'short_name' => 'VIB', 'website' => 'https://www.vib.com.vn'],
        'shb' => ['slug' => 'shb', 'name' => 'Ngân hàng TMCP Sài Gòn – Hà Nội', 'short_name' => 'SHB', 'website' => 'https://www.shb.com.vn'],
        'seabank' => ['slug' => 'seabank', 'name' => 'Ngân hàng TMCP Đông Nam Á', 'short_name' => 'SeABank', 'website' => 'https://www.seabank.com.vn'],
        'ocb' => ['slug' => 'ocb', 'name' => 'Ngân hàng TMCP Phương Đông', 'short_name' => 'OCB', 'website' => 'https://ocb.com.vn'],
        'msb' => ['slug' => 'msb', 'name' => 'Ngân hàng TMCP Hàng Hải Việt Nam', 'short_name' => 'MSB', 'website' => 'https://www.msb.com.vn'],
        'lpbank' => ['slug' => 'lpbank', 'name' => 'Ngân hàng TMCP Lộc Phát Việt Nam', 'short_name' => 'LPBank', 'website' => 'https://lpbank.com.vn'],
        'eximbank' => ['slug' => 'eximbank', 'name' => 'Ngân hàng TMCP Xuất Nhập khẩu Việt Nam', 'short_name' => 'Eximbank', 'website' => 'https://eximbank.com.vn'],
        'namabank' => ['slug' => 'namabank', 'name' => 'Ngân hàng TMCP Nam Á', 'short_name' => 'Nam A Bank', 'website' => 'https://www.namabank.com.vn'],
        'bacabank' => ['slug' => 'bacabank', 'name' => 'Ngân hàng TMCP Bắc Á', 'short_name' => 'Bac A Bank', 'website' => 'https://www.baca-bank.vn'],
        'pvcombank' => ['slug' => 'pvcombank', 'name' => 'Ngân hàng TMCP Đại Chúng Việt Nam', 'short_name' => 'PVcomBank', 'website' => 'https://www.pvcombank.com.vn'],
        'abbank' => ['slug' => 'abbank', 'name' => 'Ngân hàng TMCP An Bình', 'short_name' => 'ABBANK', 'website' => 'https://www.abbank.vn'],
    ];

    /** @return array{slug: string, name: string, short_name: string, website: string} */
    public static function get(string $slug): array
    {
        return self::ALL[$slug];
    }
}
