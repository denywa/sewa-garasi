<?php

namespace App\Services;

use App\Enums\GarageType;
use InvalidArgumentException;

/**
 * Membuat nomor & kode garasi (S001, C010, E999). Murni, tanpa database:
 * nomor terakhir per tipe diambil oleh pemanggil.
 */
final class GarageCodeGenerator
{
    /** Nomor tertinggi per tipe garasi (kode 3 digit). */
    public const MAX_NUMBER = 999;

    /**
     * Nomor berikutnya sebanyak $count, melanjutkan $lastNumber: [last+1 … last+count].
     *
     * @return list<int>
     *
     * @throws InvalidArgumentException bila $count < 1 atau melewati MAX_NUMBER
     */
    public function nextNumbers(int $lastNumber, int $count): array
    {
        if ($count < 1) {
            throw new InvalidArgumentException('Jumlah garasi minimal 1.');
        }

        if ($lastNumber + $count > self::MAX_NUMBER) {
            $remaining = self::MAX_NUMBER - $lastNumber;
            throw new InvalidArgumentException("Sisa kuota kode tipe ini: {$remaining}.");
        }

        return range($lastNumber + 1, $lastNumber + $count);
    }

    /**
     * Kode garasi untuk ditampilkan: prefix tipe + nomor 3 digit (Car, 10 → "C010").
     */
    public function format(GarageType $type, int $number): string
    {
        return $type->prefix().str_pad((string) $number, 3, '0', STR_PAD_LEFT);
    }
}
