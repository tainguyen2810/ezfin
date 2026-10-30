<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Đồng ý điều khoản khi đăng ký ứng dụng; đồng ý nhận email giới thiệu tách riêng, không bắt buộc.
            $table->timestamp('terms_accepted_at')->nullable()->after('email_verified_at');
            $table->timestamp('marketing_consent_at')->nullable()->after('terms_accepted_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['terms_accepted_at', 'marketing_consent_at']);
        });
    }
};
