<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            // income, expense, transfer_in, transfer_out. Số tiền luôn dương; chiều tiền theo loại.
            $table->string('type', 15);
            $table->decimal('amount', 18, 2);
            $table->date('occurred_on');
            $table->string('note', 255)->nullable();
            // Chuyển tiền giữa hai ví = hai dòng chung mã này, không tính vào thu/chi.
            $table->uuid('transfer_group_id')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'occurred_on']);
            $table->index(['wallet_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
