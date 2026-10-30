<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tài sản (nhà, xe, vàng...) và khoản nợ nhập tay. Tiền trong ví lấy từ bảng wallets, không lưu ở đây.
        Schema::create('net_worth_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 30);
            $table->string('name', 100);
            $table->decimal('amount', 18, 2); // giá trị ước tính (tài sản) hoặc dư nợ còn lại (nợ)
            $table->decimal('interest_rate', 7, 4)->nullable(); // %/năm, chỉ cho khoản nợ
            $table->decimal('monthly_payment', 18, 2)->nullable();
            $table->date('valued_on');
            $table->string('note', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('net_worth_items');
    }
};
