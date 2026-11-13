<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tỷ giá ngoại tệ do người vận hành nhập tay hoặc nhập file từ bảng tỷ giá công bố của ngân hàng.
        // Giữ mọi lần công bố để vẽ lịch sử; cùng nguồn, mã, thời điểm thì ghi đè (nhập lại không tạo trùng).
        Schema::create('fx_rates', function (Blueprint $table) {
            $table->id();
            $table->string('source', 60);
            $table->char('currency', 3);
            $table->dateTime('quoted_at');
            $table->decimal('buy_cash', 18, 2)->nullable();
            $table->decimal('buy_transfer', 18, 2)->nullable();
            $table->decimal('sell', 18, 2);
            $table->string('source_url', 500)->nullable();
            $table->timestamps();
            $table->unique(['source', 'currency', 'quoted_at']);
            $table->index(['currency', 'quoted_at']);
        });

        // Giá vàng, đồng/lượng.
        Schema::create('gold_prices', function (Blueprint $table) {
            $table->id();
            $table->string('source', 60);
            $table->string('product', 120);
            $table->dateTime('quoted_at');
            $table->decimal('buy', 18, 2)->nullable();
            $table->decimal('sell', 18, 2);
            $table->string('source_url', 500)->nullable();
            $table->timestamps();
            $table->unique(['source', 'product', 'quoted_at']);
            $table->index('quoted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gold_prices');
        Schema::dropIfExists('fx_rates');
    }
};
