<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbound_links', function (Blueprint $table) {
            $table->id();
            $table->char('code', 12)->unique();
            $table->foreignId('product_id')->nullable()->constrained('financial_products')->nullOnDelete();
            $table->string('name', 150);
            $table->string('partner', 100)->nullable();
            $table->string('destination_url', 2000);
            $table->string('cta_label', 40);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['product_id', 'is_active']);
        });

        // Nhật ký click: không soft delete, tự xóa sau OutboundClick::RETENTION_MONTHS tháng.
        Schema::create('outbound_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('link_id')->constrained('outbound_links')->cascadeOnDelete();
            $table->char('click_id', 24)->unique();
            $table->char('ip_hash', 64)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('referer_path', 500)->nullable();
            $table->timestamp('created_at')->index();

            $table->index(['link_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbound_clicks');
        Schema::dropIfExists('outbound_links');
    }
};
