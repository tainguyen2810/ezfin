<?php

namespace App\Modules\Core\Enums;

/** Vai trò nội bộ. Khách hàng của ứng dụng không có vai trò nào và không vào được trang quản trị. */
enum Role: string
{
    case SuperAdmin = 'super-admin';
    case Editor = 'editor';
    case DataOperator = 'data-operator';
    case Finance = 'finance';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Quản trị cao nhất',
            self::Editor => 'Biên tập nội dung',
            self::DataOperator => 'Vận hành dữ liệu',
            self::Finance => 'Đối tác và tài chính',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $role) => [$role->value => $role->label()])->all();
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
