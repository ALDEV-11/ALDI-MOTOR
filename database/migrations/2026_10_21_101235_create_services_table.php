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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->comment('Estimasi durasi pengerjaan dalam menit');
            $table->unsignedBigInteger('price_estimate')->comment('Estimasi biaya dalam satuan Rupiah (IDR)');
            $table->unsignedSmallInteger('reminder_interval_days')->nullable()->comment('Siklus pengingat servis berikutnya (hari)');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
