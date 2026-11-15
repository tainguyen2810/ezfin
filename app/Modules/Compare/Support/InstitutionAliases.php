<?php

namespace App\Modules\Compare\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Tên viết tắt, mã cổ phiếu của ngân hàng để tìm nhanh ("vcb" ra Vietcombank).
 * Danh sách sẵn dùng để điền lần đầu; người quản trị sửa ở trường "Tên gọi khác" của ngân hàng.
 */
final class InstitutionAliases
{
    /** slug ngân hàng => tên gọi khác, cách nhau dấu phẩy */
    public const KNOWN = [
        'vietcombank' => 'VCB, Ngoại thương',
        'bidv' => 'BID, Đầu tư và Phát triển',
        'vietinbank' => 'CTG, VTB, Công Thương',
        'agribank' => 'VBA, Nông nghiệp',
        'techcombank' => 'TCB, Kỹ Thương',
        'mb' => 'MBB, MBBank, Quân đội',
        'acb' => 'ACB, Á Châu',
        'vpbank' => 'VPB, Việt Nam Thịnh Vượng',
        'sacombank' => 'STB, Sài Gòn Thương Tín',
        'tpbank' => 'TPB, Tiên Phong',
        'hdbank' => 'HDB',
        'vib' => 'VIB, Quốc tế',
        'shb' => 'SHB, Sài Gòn Hà Nội',
        'msb' => 'MSB, Hàng Hải',
        'ocb' => 'OCB, Phương Đông',
        'seabank' => 'SSB, Đông Nam Á',
        'eximbank' => 'EIB, Xuất Nhập khẩu',
        'lpbank' => 'LPB, Lộc Phát',
    ];

    /**
     * Lọc sản phẩm: mỗi từ khóa phải khớp tên sản phẩm hoặc tên, tên ngắn, tên gọi khác của ngân hàng.
     * "vcb visa" → thẻ Visa của Vietcombank.
     */
    public static function searchProducts(Builder $query, string $search, string $nameColumn = 'name'): Builder
    {
        foreach (self::terms($search) as $term) {
            $like = '%'.addcslashes($term, '%_\\').'%';
            $query->where(fn (Builder $query) => $query
                ->where($query->qualifyColumn($nameColumn), 'like', $like)
                ->orWhereHas('institution', fn (Builder $bank) => self::matchInstitution($bank, $like)));
        }

        return $query;
    }

    public static function searchInstitutions(Builder $query, string $search): Builder
    {
        foreach (self::terms($search) as $term) {
            $query->where(fn (Builder $bank) => self::matchInstitution($bank, '%'.addcslashes($term, '%_\\').'%'));
        }

        return $query;
    }

    private static function matchInstitution(Builder $bank, string $like): Builder
    {
        return $bank->where(fn (Builder $bank) => $bank->where('short_name', 'like', $like)
            ->orWhere('name', 'like', $like)
            ->orWhere('slug', 'like', $like)
            ->orWhere('search_aliases', 'like', $like));
    }

    /** @return list<string> */
    private static function terms(string $search): array
    {
        return array_slice(array_values(array_filter(preg_split('/[\s,]+/u', mb_strtolower(trim($search))) ?: [])), 0, 5);
    }
}
