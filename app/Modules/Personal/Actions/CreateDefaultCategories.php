<?php

namespace App\Modules\Personal\Actions;

use App\Models\User;
use App\Modules\Personal\Enums\CategoryType;
use App\Modules\Personal\Models\Category;

/** Bộ danh mục thu/chi mặc định cho tài khoản mới; người dùng sửa, ẩn hoặc thêm được. */
class CreateDefaultCategories
{
    /** [tên, icon, màu] */
    public const EXPENSE = [
        ['Ăn uống', 'cake', 'orange'],
        ['Nhà ở', 'home', 'red'],
        ['Đi lại', 'truck', 'amber'],
        ['Mua sắm', 'shopping-bag', 'blue'],
        ['Hóa đơn & tiện ích', 'bolt', 'teal'],
        ['Giải trí', 'film', 'violet'],
        ['Sức khỏe', 'heart', 'pink'],
        ['Học tập', 'academic-cap', 'blue'],
        ['Gia đình', 'users', 'amber'],
        ['Khác', 'ellipsis-horizontal', 'slate'],
    ];

    public const INCOME = [
        ['Lương', 'briefcase', 'green'],
        ['Thưởng', 'trophy', 'amber'],
        ['Kinh doanh', 'chart-bar', 'teal'],
        ['Lãi tiết kiệm', 'banknotes', 'green'],
        ['Thu khác', 'ellipsis-horizontal', 'slate'],
    ];

    public function handle(User $user): void
    {
        if (Category::withoutGlobalScopes()->where('user_id', $user->id)->exists()) {
            return;
        }

        foreach ([CategoryType::Expense->value => self::EXPENSE, CategoryType::Income->value => self::INCOME] as $type => $defaults) {
            foreach ($defaults as $sort => [$name, $icon, $color]) {
                $category = new Category(['name' => $name, 'type' => $type, 'icon' => $icon, 'color' => $color, 'sort' => $sort]);
                $category->user_id = $user->id;
                $category->save();
            }
        }
    }
}
