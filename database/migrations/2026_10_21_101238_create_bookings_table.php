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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code', 30)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->restrictOnDelete();
            $table->date('booking_date');
            $table->unsignedSmallInteger('queue_order')->comment('Nomor urut antrean harian');
            $table->time('estimated_arrival_at')->comment('Estimasi jam kedatangan pelanggan ke bengkel');
            $table->string('status', 30)->default('pending');

            /**
             * KOLOM BANTU STORED GENERATED UNTUK UNIQUE CONSTRAINT PARSIAL:
             * Di MySQL, nilai NULL tidak dianggap duplikat pada UNIQUE index.
             * Kolom ini bernilai queue_order HANYA jika status BUKAN 'cancelled'.
             * Jika booking dibatalkan ('cancelled'), kolom ini otomatis menjadi NULL.
             * Hasilnya: Slot antrean yang dibatalkan dapat diisi kembali tanpa melanggar UNIQUE key!
             */
            $table->unsignedSmallInteger('active_queue_order')
                ->storedAs("CASE WHEN status != 'cancelled' THEN queue_order ELSE NULL END")
                ->nullable()
                ->comment('Helper column untuk mencegah nomor antrean ganda pada booking aktif di tanggal yang sama');

            $table->text('complaint')->nullable()->comment('Keluhan awal atau catatan kendala dari pelanggan');
            $table->text('cancel_reason')->nullable()->comment('Alasan pembatalan booking');

            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            // Index performa query pencarian dan validasi kuota
            $table->index(['booking_date', 'status']);
            $table->index('user_id');
            $table->index('vehicle_id');

            // Unique constraint parsial: Hanya 1 booking AKTIF untuk (booking_date, queue_order)
            $table->unique(['booking_date', 'active_queue_order'], 'uniq_active_booking_queue');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
