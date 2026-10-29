<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Thông số tài khoản thanh toán. Để trống = chưa có thông tin chính thức, khác với 0 (miễn phí).
        Schema::create('bank_account_details', function (Blueprint $table) {
            $table->foreignId('product_id')->primary()->constrained('financial_products')->cascadeOnDelete();
            $table->decimal('monthly_fee', 18, 2)->nullable(); // phí quản lý tài khoản/tháng
            $table->string('monthly_fee_note')->nullable();
            $table->decimal('min_balance', 18, 2)->nullable();
            $table->decimal('transfer_fee', 18, 2)->nullable(); // chuyển khoản liên ngân hàng online/giao dịch
            $table->decimal('atm_withdrawal_fee', 18, 2)->nullable(); // rút tiền ATM nội mạng/giao dịch
            $table->json('features')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_account_details');
    }
};
