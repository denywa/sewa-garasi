<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GarageType;
use App\Enums\ReportType;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReportRequest;
use App\Models\Payment;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * UC-09 Laporan Sewa Garasi. Setiap baris = jumlah transaksi & total pendapatan dari tabel payments
 * (sewa awal + perpanjangan), dikelompokkan berdasarkan tanggal bayar (payments.created_at).
 */
class ReportController extends Controller
{
    /**
     * Tampilkan form filter dan tabel laporan sesuai jenis yang dipilih.
     */
    public function index(ReportRequest $request): View
    {
        $type = $request->reportType();
        $year = $request->year();
        $month = $request->month();

        $rows = match ($type) {
            ReportType::Monthly => $this->monthly($year),
            ReportType::Daily => $this->daily($year, $month),
            ReportType::GarageType => $this->perGarageType($year, $month),
            ReportType::Tenant => $this->perTenant($year, $month),
        };

        return view('admin.reports.index', [
            'types' => ReportType::cases(),
            'type' => $type,
            'year' => $year,
            'month' => $month,
            'rows' => $rows,
        ]);
    }

    /**
     * 12 baris Januari–Desember dalam satu tahun.
     *
     * @return list<array{label: string, count: int, total: int}>
     */
    private function monthly(int $year): array
    {
        $from = Carbon::create($year)->startOfYear();
        $totals = $this->payments($from, $from->copy()->endOfYear())
            ->selectRaw('MONTH(payments.created_at) AS k')
            ->groupBy('k')
            ->get();

        $labels = [];
        foreach (range(1, 12) as $month) {
            $labels[$month] = Carbon::create($year, $month)->translatedFormat('F');
        }

        return $this->fill($labels, $totals);
    }

    /**
     * Satu baris per tanggal dalam satu bulan.
     *
     * @return list<array{label: string, count: int, total: int}>
     */
    private function daily(int $year, int $month): array
    {
        $from = Carbon::create($year, $month)->startOfMonth();
        $totals = $this->payments($from, $from->copy()->endOfMonth())
            ->selectRaw('DAY(payments.created_at) AS k')
            ->groupBy('k')
            ->get();

        $labels = [];
        foreach (range(1, $from->daysInMonth) as $day) {
            $labels[$day] = $from->copy()->day($day)->translatedFormat('d M Y');
        }

        return $this->fill($labels, $totals);
    }

    /**
     * 3 baris (Sepeda, Mobil, Mobil Listrik) dalam satu bulan. payments → rentals → garages.
     *
     * @return list<array{label: string, count: int, total: int}>
     */
    private function perGarageType(int $year, int $month): array
    {
        $from = Carbon::create($year, $month)->startOfMonth();
        $totals = $this->payments($from, $from->copy()->endOfMonth())
            ->join('rentals', 'rentals.id', '=', 'payments.rental_id')
            ->join('garages', 'garages.id', '=', 'rentals.garage_id')
            ->addSelect('garages.type AS k')
            ->groupBy('garages.type')
            ->get();

        $labels = [];
        foreach (GarageType::cases() as $garageType) {
            $labels[$garageType->value] = $garageType->label();
        }

        return $this->fill($labels, $totals);
    }

    /**
     * Satu baris per penyewa yang bertransaksi dalam satu bulan. payments → rentals → vehicles → tenants → users.
     *
     * @return list<array{label: string, count: int, total: int}>
     */
    private function perTenant(int $year, int $month): array
    {
        $from = Carbon::create($year, $month)->startOfMonth();

        return $this->payments($from, $from->copy()->endOfMonth())
            ->join('rentals', 'rentals.id', '=', 'payments.rental_id')
            ->join('vehicles', 'vehicles.id', '=', 'rentals.vehicle_id')
            ->join('tenants', 'tenants.user_id', '=', 'vehicles.tenant_id')
            ->join('users', 'users.id', '=', 'tenants.user_id')
            ->addSelect('users.name', 'tenants.nik')
            ->groupBy('users.id', 'users.name', 'tenants.nik')
            ->orderBy('users.name')
            ->get()
            ->map(fn (object $row) => [
                'label' => "{$row->name} (NIK {$row->nik})",
                'count' => (int) $row->trx,
                'total' => (int) $row->total,
            ])
            ->all();
    }

    /**
     * Query dasar: pembayaran pada rentang tanggal bayar, dengan jumlah transaksi (trx) & total pendapatan (total).
     */
    private function payments(Carbon $from, Carbon $to): Builder
    {
        return Payment::query()->toBase()
            ->selectRaw('COUNT(payments.id) AS trx, SUM(payments.amount) AS total')
            ->whereBetween('payments.created_at', [$from, $to]);
    }

    /**
     * Gabungkan hasil GROUP BY dengan semua label periode/tipe; yang tidak ada transaksinya bernilai 0.
     *
     * @param  array<int|string, string>  $labels  [kunci grup => label]
     * @param  Collection<int, object>  $totals  hasil query dengan kolom k, trx, total
     * @return list<array{label: string, count: int, total: int}>
     */
    private function fill(array $labels, Collection $totals): array
    {
        $byKey = $totals->keyBy('k');

        $rows = [];
        foreach ($labels as $key => $label) {
            $rows[] = [
                'label' => $label,
                'count' => (int) ($byKey[$key]->trx ?? 0),
                'total' => (int) ($byKey[$key]->total ?? 0),
            ];
        }

        return $rows;
    }
}
