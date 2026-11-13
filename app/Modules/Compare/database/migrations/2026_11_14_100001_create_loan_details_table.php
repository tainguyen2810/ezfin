<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Thông số khoản vay. Để trống = ngân hàng chưa công bố, khác với 0.
        Schema::create('loan_details', function (Blueprint $table) {
            $table->foreignId('product_id')->primary()->constrained('financial_products')->cascadeOnDelete();
            $table->string('kind', 20)->index(); // LoanKind
            $table->decimal('promo_rate', 7, 4)->nullable(); // lãi ưu đãi %/năm (hoặc lãi cố định nếu không thả nổi)
            $table->unsignedSmallInteger('promo_months')->nullable(); // số tháng áp dụng lãi ưu đãi; trống = cố định cả kỳ hạn
            $table->decimal('floating_margin', 7, 4)->nullable(); // biên độ cộng thêm sau ưu đãi, điểm %
            $table->string('floating_note')->nullable(); // cách tính lãi sau ưu đãi, ví dụ "lãi tiết kiệm 24 tháng + 3,5%"
            $table->decimal('min_amount', 18, 2)->nullable();
            $table->decimal('max_amount', 18, 2)->nullable();
            $table->unsignedSmallInteger('max_term_months')->nullable();
            $table->decimal('max_ltv', 7, 4)->nullable(); // cho vay tối đa % giá trị tài sản bảo đảm
            $table->decimal('min_monthly_income', 18, 2)->nullable();
            $table->decimal('processing_fee', 7, 4)->nullable(); // phí hồ sơ/giải ngân, % số tiền vay
            $table->decimal('prepayment_fee', 7, 4)->nullable(); // phí trả nợ trước hạn cao nhất, % số tiền trả trước
            $table->string('prepayment_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_details');
    }
};
