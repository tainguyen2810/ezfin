<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mã của dòng sao kê đã nhập (Actions\ImportStatement), để nhập lại cùng sao kê không tạo trùng.
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('import_hash', 40)->nullable()->after('recurring_transaction_id');
            $table->unique(['wallet_id', 'import_hash']);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique(['wallet_id', 'import_hash']);
            $table->dropColumn('import_hash');
        });
    }
};
