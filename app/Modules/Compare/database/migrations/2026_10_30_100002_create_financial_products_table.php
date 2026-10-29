<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_products', function (Blueprint $table) {
            $table->id();
            $table->string('type', 32);
            // Không xóa được tổ chức khi còn sản phẩm.
            $table->foreignId('institution_id')->constrained('financial_institutions')->restrictOnDelete();
            $table->string('name');
            $table->string('slug', 150)->unique();
            $table->string('status', 20)->default('draft');
            $table->text('summary')->nullable();
            $table->json('pros')->nullable();
            $table->json('cons')->nullable();
            $table->string('image_path')->nullable();
            $table->string('source_url', 500)->nullable();
            $table->string('source_note', 500)->nullable();
            $table->date('verified_at')->nullable();
            $table->date('next_review_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index('next_review_at');
        });

        Schema::create('credit_card_details', function (Blueprint $table) {
            $table->foreignId('product_id')->primary()->constrained('financial_products')->cascadeOnDelete();
            $table->string('network', 20)->nullable();
            $table->decimal('annual_fee', 18, 2)->nullable();
            $table->string('annual_fee_note')->nullable();
            $table->decimal('issuance_fee', 18, 2)->nullable();
            $table->decimal('min_monthly_income', 18, 2)->nullable();
            $table->decimal('interest_rate', 7, 4)->nullable();
            $table->unsignedSmallInteger('interest_free_days')->nullable();
            $table->decimal('cashback_rate', 7, 4)->nullable();
            $table->decimal('cashback_monthly_cap', 18, 2)->nullable();
            $table->decimal('foreign_transaction_fee', 7, 4)->nullable();
            $table->json('benefits')->nullable();
            $table->timestamps();

            $table->index('annual_fee');
            $table->index('min_monthly_income');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_card_details');
        Schema::dropIfExists('financial_products');
    }
};
