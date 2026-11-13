<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chuyển đổi affiliate do đối tác báo về (postback) hoặc nhập từ file đối soát.
 * Lưu bản sao link và trang nguồn lúc ghi, vì nhật ký click tự xóa sau 12 tháng mà số liệu doanh thu cần giữ.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_conversions', function (Blueprint $table) {
            $table->id();
            $table->string('network', 50);
            $table->string('external_id', 100);
            $table->char('click_id', 24)->nullable()->index();
            $table->foreignId('link_id')->nullable()->constrained('outbound_links')->nullOnDelete();
            $table->string('source_path', 500)->nullable();
            $table->string('status', 20)->default('pending');
            $table->decimal('commission', 18, 2)->default(0);
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->unique(['network', 'external_id']);
            $table->index(['link_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_conversions');
    }
};
