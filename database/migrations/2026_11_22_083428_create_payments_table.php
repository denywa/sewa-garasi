<?php

use App\Enums\PaymentType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pembayaran sewa awal & tiap perpanjangan. Satu-satunya catatan uang; created_at = tanggal bayar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            // FK ke rentals.id. RESTRICT = sewa yang sudah dibayar tidak bisa dihapus (riwayat uang aman).
            $table->foreignId('rental_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->enum('type', array_column(PaymentType::cases(), 'value'));
            $table->unsignedInteger('amount');
            $table->string('proof_path');
            $table->date('end_date'); // tanggal selesai yang dibayar (awal / hasil perpanjangan)
            $table->timestamps();
        });

        // CHECK: jumlah bayar harus lebih dari 0.
        DB::statement('ALTER TABLE payments ADD CONSTRAINT payments_amount_check CHECK (amount > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
