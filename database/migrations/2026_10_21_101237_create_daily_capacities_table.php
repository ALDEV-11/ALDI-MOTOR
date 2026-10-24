<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('daily_capacities', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->unsignedSmallInteger('quota')->nullable()->comment('Override kuota harian khusus. Jika NULL, gunakan default kuota bengkel.');
            $table->boolean('is_open')->default(true)->comment('True = bengkel buka menerima servis, False = libur/tutup');
            $table->string('note', 255)->nullable()->comment('Catatan operasional, misal: Libur Nasional / Cuti Bersama');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_capacities');
    }
};
