<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Chỉ lưu phần quản trị viên đã đổi; loại thông báo chưa có dòng nào chạy theo mặc định trong code.
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80)->unique();
            $table->boolean('enabled')->default(true);
            $table->json('channels')->nullable();
            $table->json('recipient_roles')->nullable();
            $table->json('extra_emails')->nullable();
            $table->unsignedSmallInteger('repeat_hours')->nullable();
            $table->timestamps();
        });

        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80);
            $table->string('channel', 20);
            $table->string('subject', 255)->nullable();
            $table->string('greeting', 255)->nullable();
            $table->text('body')->nullable();
            $table->string('action_label', 80)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['key', 'channel']);
        });

        // Nhật ký gửi: không lưu địa chỉ hay tên người nhận, chỉ số lượng. Giữ 90 ngày.
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80);
            $table->string('channel', 20);
            $table->unsignedInteger('recipients')->default(0);
            $table->string('status', 20);
            $table->string('error', 500)->nullable();
            $table->boolean('test')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['key', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('notification_settings');
    }
};
