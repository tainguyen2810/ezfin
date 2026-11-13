<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Câu hỏi thường gặp cuối bài viết (hiện kèm dữ liệu FAQPage), lưu cả trong lịch sử sửa. */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['articles', 'article_revisions'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->json('faq')->nullable()->after('references');
            });
        }
    }

    public function down(): void
    {
        foreach (['articles', 'article_revisions'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('faq');
            });
        }
    }
};
