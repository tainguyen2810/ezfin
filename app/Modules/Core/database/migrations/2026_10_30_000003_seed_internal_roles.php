<?php

use App\Modules\Core\Enums\Role;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

/** Vai trò nội bộ là dữ liệu bắt buộc của hệ thống nên được tạo cùng migration. */
return new class extends Migration
{
    public function up(): void
    {
        foreach (Role::values() as $name) {
            RoleModel::findOrCreate($name, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        RoleModel::whereIn('name', Role::values())->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
