<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Khoản thu/chi lặp lại (lương, tiền nhà, trả thẻ, trả góp...): tự ghi giao dịch, nhắc để xác nhận, hoặc chỉ nhắc hạn.
        Schema::create('recurring_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_id')->nullable()->constrained()->cascadeOnDelete(); // null khi chỉ nhắc hạn
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 100);
            $table->string('type', 15); // income, expense
            $table->decimal('amount', 18, 2);
            $table->string('frequency', 10); // weekly, monthly, quarterly, yearly
            $table->date('starts_on'); // mốc tính các kỳ (ngày trong tháng, thứ trong tuần)
            $table->date('ends_on')->nullable();
            $table->date('next_due_on')->nullable(); // null = đã hết kỳ
            $table->date('last_occurrence_on')->nullable(); // kỳ gần nhất đã ghi nhận hoặc bỏ qua
            $table->string('mode', 10); // auto, confirm, remind (xem Enums\RecurringMode)
            $table->unsignedTinyInteger('remind_days_before')->nullable(); // null = không gửi email nhắc
            $table->date('reminded_for')->nullable(); // kỳ đã gửi email nhắc, tránh gửi lại
            $table->timestamps();

            $table->index(['next_due_on', 'mode']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('recurring_transaction_id')->nullable()->after('transfer_group_id')
                ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recurring_transaction_id');
        });
        Schema::dropIfExists('recurring_transactions');
    }
};
