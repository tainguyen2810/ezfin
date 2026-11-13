<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Người nhận email (bản tin tuần, báo lãi suất tăng). Chỉ lưu sau khi người dùng bấm xác nhận trong email
 * (double opt-in); hủy hết thì xóa hẳn dòng, không giữ email.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email', 191)->unique();
            $table->char('token', 40)->unique();
            $table->boolean('weekly_digest')->default(false);
            $table->string('source_path', 255)->nullable();
            $table->timestamp('consent_at');
            $table->timestamp('confirmed_at');
            $table->timestamp('weekly_sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('rate_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscriber_id')->constrained('subscribers')->cascadeOnDelete();
            $table->unsignedTinyInteger('term_months');
            $table->decimal('baseline_rate', 7, 4)->nullable();
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamps();

            $table->unique(['subscriber_id', 'term_months']);
            $table->index('term_months');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_alerts');
        Schema::dropIfExists('subscribers');
    }
};
