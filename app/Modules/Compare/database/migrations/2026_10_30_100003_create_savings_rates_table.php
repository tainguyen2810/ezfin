<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mỗi dòng: lãi suất của một sản phẩm tiết kiệm theo kỳ hạn, kênh, hình thức lĩnh lãi, mức tiền, ngày hiệu lực.
        // Giữ lịch sử theo ngày hiệu lực; lãi suất đang áp dụng là dòng có ngày hiệu lực gần nhất không ở tương lai.
        Schema::create('savings_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('financial_products')->cascadeOnDelete();
            $table->unsignedSmallInteger('term_months'); // 0 = không kỳ hạn
            $table->string('channel', 20);
            $table->string('interest_payment', 20)->default('end_of_term');
            $table->decimal('min_amount', 18, 2)->default(0);
            $table->decimal('max_amount', 18, 2)->nullable();
            $table->decimal('rate', 7, 4); // %/năm
            $table->date('effective_from');
            $table->timestamps();

            // Khóa để import lại không tạo trùng.
            $table->unique(['product_id', 'term_months', 'channel', 'interest_payment', 'min_amount', 'effective_from'], 'savings_rates_natural_key');
            $table->index(['term_months', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('savings_rates');
    }
};
