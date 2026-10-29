<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_institutions', function (Blueprint $table) {
            $table->id();
            $table->string('type', 32)->index();
            $table->string('name');
            $table->string('short_name', 100);
            $table->string('slug', 120)->unique();
            $table->string('logo_path')->nullable();
            $table->string('website')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_institutions');
    }
};
