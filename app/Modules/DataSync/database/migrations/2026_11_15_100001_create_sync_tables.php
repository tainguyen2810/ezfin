<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mỗi nguồn dữ liệu tự động (một trang của một ngân hàng/đơn vị): trạng thái lần chạy gần nhất để quản trị theo dõi.
        Schema::create('sync_sources', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80)->unique();
            $table->string('kind', 20)->index();
            $table->string('name');
            $table->string('url', 500);
            $table->boolean('is_enabled')->default(true);
            $table->boolean('auto_approve')->default(false);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_failure_at')->nullable();
            $table->text('last_error')->nullable();
            $table->unsignedInteger('consecutive_failures')->default(0);
            $table->timestamp('last_alerted_at')->nullable();
            $table->timestamps();
        });

        // Dữ liệu lấy về chờ duyệt. Chỉ khi quản trị viên duyệt (hoặc nguồn bật tự duyệt) mới ghi vào bảng sản phẩm.
        Schema::create('sync_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained('sync_sources')->cascadeOnDelete();
            $table->string('status', 20)->default('pending')->index();
            $table->json('payload');
            $table->char('payload_hash', 64);
            $table->json('warnings')->nullable();
            $table->date('effective_from')->nullable();
            $table->timestamp('fetched_at');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();

            $table->index(['source_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_drafts');
        Schema::dropIfExists('sync_sources');
    }
};
