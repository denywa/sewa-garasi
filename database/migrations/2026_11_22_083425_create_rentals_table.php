<?php

use App\Enums\RentalType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sewa satu garasi oleh satu kendaraan. end_date inklusif = MAX(payments.end_date),
 * diperbarui dalam transaksi yang sama dengan insert pembayaran perpanjangan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('garage_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->enum('rental_type', array_column(RentalType::cases(), 'value'));
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('battery_remaining_kwh', 6, 2)->nullable();
            $table->timestamps();

            // Mempercepat cek ketersediaan garasi pada tanggal tertentu.
            $table->index(['garage_id', 'start_date', 'end_date']);
        });

        DB::statement('ALTER TABLE rentals ADD CONSTRAINT rentals_dates_check CHECK (end_date >= start_date)');
        DB::statement('ALTER TABLE rentals ADD CONSTRAINT rentals_battery_check
            CHECK (battery_remaining_kwh IS NULL OR battery_remaining_kwh >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('rentals');
    }
};
