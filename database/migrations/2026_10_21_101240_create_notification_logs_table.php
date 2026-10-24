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
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->string('type', 50)->comment('Jenis notifikasi, mis: booking_confirmation, status_update, reminder');
            $table->string('channel', 30)->default('whatsapp');
            $table->string('phone', 25);
            $table->text('message');
            $table->timestamp('opened_at')->nullable()->comment('Waktu link wa.me diklik/dibuka');
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('booking_id');
            $table->index('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
