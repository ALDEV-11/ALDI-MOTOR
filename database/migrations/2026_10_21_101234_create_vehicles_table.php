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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('plate_number', 20);
            $table->string('brand', 50);
            $table->string('model', 100);
            $table->unsignedSmallInteger('year');
            $table->softDeletes();
            $table->timestamps();

            // Pelanggan tidak boleh mendaftarkan plat nomor yang sama lebih dari satu kali
            $table->unique(['user_id', 'plate_number']);
            $table->index('plate_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
