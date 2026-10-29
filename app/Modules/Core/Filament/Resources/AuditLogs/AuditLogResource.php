<?php

namespace App\Modules\Core\Filament\Resources\AuditLogs;

use App\Modules\Core\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Modules\Core\Filament\Resources\AuditLogs\Tables\AuditLogsTable;
use App\Modules\Core\Models\AuditLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/** Nhật ký audit chỉ đọc: không có trang tạo hay sửa. */
class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Hệ thống';

    protected static ?string $modelLabel = 'nhật ký';

    // Tiếng Việt không viết hoa mọi chữ cái đầu từ.
    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $pluralModelLabel = 'Nhật ký hoạt động';

    protected static ?int $navigationSort = 90;

    public static function table(Table $table): Table
    {
        return AuditLogsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
        ];
    }
}
