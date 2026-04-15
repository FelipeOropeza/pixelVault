<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedBigInteger('storage_limit_bytes')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Adiciona colunas de plano e armazenamento à tabela users
        // feito aqui pois a FK precisa que 'plans' já exista
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->unsignedBigInteger('storage_used_bytes')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn(['plan_id', 'storage_used_bytes']);
        });

        Schema::dropIfExists('plans');
    }
};
