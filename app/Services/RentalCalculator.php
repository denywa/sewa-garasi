<?php

namespace App\Services;

use App\Enums\RentalType;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * Perhitungan murni (tanpa database) untuk jumlah hari, tanggal selesai, dan biaya sewa.
 *
 * N = jumlah hari pada bulan tanggal mulai. Sewa harian 1 … N−1 hari, sewa bulanan N hari.
 */
final class RentalCalculator
{
    /** Sewa bulanan = 25 × harga harian. */
    private const MONTHLY_PRICE_MULTIPLIER = 25;

    /**
     * N: jumlah hari pada bulan tanggal mulai (28/29/30/31).
     */
    public function daysInMonth(CarbonInterface $start): int
    {
        return $start->daysInMonth;
    }

    /**
     * Jumlah hari maksimal untuk sewa harian: N − 1.
     */
    public function maxDailyDays(CarbonInterface $start): int
    {
        return $this->daysInMonth($start) - 1;
    }

    /**
     * Lama sewa dalam hari. Harian memakai $days (1 … N−1), bulanan selalu N hari.
     *
     * @throws InvalidArgumentException bila sewa harian di luar 1 … N−1 hari
     */
    public function rentalDays(RentalType $type, CarbonInterface $start, ?int $days): int
    {
        if ($type === RentalType::Monthly) {
            return $this->daysInMonth($start);
        }

        $max = $this->maxDailyDays($start);
        if ($days === null || $days < 1 || $days > $max) {
            throw new InvalidArgumentException("Jumlah hari sewa harian harus 1 – {$max}.");
        }

        return $days;
    }

    /**
     * Tanggal selesai inklusif: tanggal mulai + jumlah hari − 1.
     */
    public function endDate(CarbonInterface $start, int $days): CarbonImmutable
    {
        return CarbonImmutable::instance($start)->addDays($days - 1);
    }

    /**
     * Biaya sewa garasi: harian = harga × hari, bulanan = 25 × harga harian.
     */
    public function rentCost(int $dailyPrice, RentalType $type, int $days): int
    {
        return match ($type) {
            RentalType::Daily => $dailyPrice * $days,
            RentalType::Monthly => $dailyPrice * self::MONTHLY_PRICE_MULTIPLIER,
        };
    }

    /**
     * Biaya isi listrik sampai penuh: (kapasitas − sisa) × harga per kWh, dibulatkan ke Rupiah.
     *
     * @throws InvalidArgumentException bila sisa baterai negatif atau melebihi kapasitas
     */
    public function chargingCost(float $capacityKwh, float $remainingKwh, int $pricePerKwh): int
    {
        if ($remainingKwh < 0 || $remainingKwh > $capacityKwh) {
            throw new InvalidArgumentException('Sisa baterai harus antara 0 dan kapasitas baterai.');
        }

        return (int) round(($capacityKwh - $remainingKwh) * $pricePerKwh);
    }

    /**
     * Sisa biaya perpanjangan: selisih hari (tanggal selesai baru − lama) × harga harian.
     *
     * @throws InvalidArgumentException bila tanggal selesai baru tidak setelah tanggal lama
     */
    public function extensionCost(CarbonInterface $oldEnd, CarbonInterface $newEnd, int $dailyPrice): int
    {
        if ($newEnd->lessThanOrEqualTo($oldEnd)) {
            throw new InvalidArgumentException('Tanggal selesai baru harus setelah tanggal selesai lama.');
        }

        return (int) $oldEnd->diffInDays($newEnd) * $dailyPrice;
    }
}
