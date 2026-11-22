<?php

namespace App\Models;

use App\Enums\PaymentType;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pembayaran (sewa awal / perpanjangan) beserta bukti bayar dan tanggal selesai yang dibayar.
 */
// Fillable: kolom yang boleh diisi sekaligus lewat create()/update(); kolom lain diabaikan (aman dari input liar).
#[Fillable(['rental_id', 'type', 'amount', 'proof_path', 'end_date'])]
class Payment extends Model
{
    // HasFactory: mengaktifkan Payment::factory() untuk membuat data contoh di test.
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /**
     * Cast: mengubah nilai kolom saat dibaca/disimpan, tipe kolom di DB tetap.
     * - type: string "initial" ↔ enum PaymentType::Initial.
     * - end_date: string ↔ objek Carbon.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PaymentType::class,
            'amount' => 'integer',
            'end_date' => 'date',
        ];
    }

    /**
     * Relasi N:1 — sewa yang dibayar (payments.rental_id).
     *
     * @return BelongsTo<Rental, $this>
     */
    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }
}
