<?php

namespace App\Enums;

/**
 * Jenis laporan sewa garasi (UC-09). Semua dihitung dari pembayaran berdasarkan tanggal bayar.
 */
enum ReportType: string
{
    case Monthly = 'monthly';
    case Daily = 'daily';
    case GarageType = 'garage_type';
    case Tenant = 'tenant';

    /**
     * Nama jenis laporan untuk ditampilkan.
     */
    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Per Bulan',
            self::Daily => 'Per Hari',
            self::GarageType => 'Per Tipe Garasi',
            self::Tenant => 'Per Penyewa',
        };
    }

    /**
     * Judul kolom pertama tabel laporan.
     */
    public function rowLabel(): string
    {
        return match ($this) {
            self::Monthly => 'Bulan',
            self::Daily => 'Tanggal',
            self::GarageType => 'Tipe Garasi',
            self::Tenant => 'Penyewa',
        };
    }

    /**
     * Laporan per bulan cukup memilih tahun; jenis lain memilih bulan & tahun.
     */
    public function needsMonth(): bool
    {
        return $this !== self::Monthly;
    }
}
