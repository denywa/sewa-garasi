<?php

namespace App\Http\Controllers\Tenant;

use App\Enums\GarageType;
use App\Enums\RentalType;
use App\Enums\VehicleType;
use App\Http\Controllers\Controller;
use App\Http\Requests\RentalSearchRequest;
use App\Http\Requests\StoreRentalRequest;
use App\Models\Garage;
use App\Models\Rental;
use App\Services\RentalCalculator;
use App\Services\RentalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * UC-04 Sewa Baru (isi data kendaraan → pilih garasi tersedia & upload bukti bayar) dan
 * UC-10 Sewa Saya (daftar & detail sewa milik penyewa).
 */
class RentalController extends Controller
{
    /**
     * Langkah 1: form kendaraan, tipe sewa, dan tipe garasi.
     */
    public function create(RentalCalculator $calculator): View
    {
        return view('tenant.rentals.create', [
            'vehicleTypes' => VehicleType::cases(),
            'rentalTypes' => RentalType::cases(),
            'garageTypes' => GarageType::cases(),
            'maxDays' => $calculator->maxDailyDays(today()),
            'monthDays' => $calculator->daysInMonth(today()),
        ]);
    }

    /**
     * Langkah 2: tanggal mulai (hari ini) & selesai, garasi tersedia hari ini beserta total biaya masing-masing.
     */
    public function choose(RentalSearchRequest $request, RentalCalculator $calculator, RentalService $service): View
    {
        $data = $request->rentalData();
        $start = today();
        $end = $calculator->endDate($start, $calculator->rentalDays($data['rental_type'], $start, $data['days']));

        $garages = Garage::query()
            ->availableOn($start)
            ->where('type', $request->enum('garage_type', GarageType::class))
            ->orderBy('number')
            ->get();

        // Rincian biaya (sewa + listrik) per garasi, karena harga bisa berbeda antar garasi.
        $costs = $garages->mapWithKeys(fn (Garage $garage) => [$garage->id => $service->costBreakdown(
            $garage, $data['rental_type'], $start, $data['days'], $data['battery_capacity_kwh'], $data['battery_remaining_kwh'],
        )]);

        return view('tenant.rentals.choose', [
            'search' => $request->validated(), // dikirim ulang sebagai hidden field saat simpan
            'data' => $data,
            'start' => $start,
            'end' => $end,
            'garages' => $garages,
            'costs' => $costs,
        ]);
    }

    /**
     * Simpan sewa lewat RentalService. Penolakan dari service tampil sebagai error form.
     */
    public function store(StoreRentalRequest $request, RentalService $service): RedirectResponse
    {
        $rental = $service->create(
            $request->user()->tenant,
            $request->rentalData() + ['garage_id' => $request->integer('garage_id')],
            $request->file('proof'),
        );

        return redirect()->route('tenant.rentals.show', $rental)->with('status', 'Sewa berhasil disimpan.');
    }

    /**
     * UC-10 Sewa Saya: semua sewa milik penyewa (lewat kendaraannya), terbaru di atas, dengan total dibayar.
     */
    public function index(Request $request): View
    {
        $rentals = Rental::query()
            ->whereRelation('vehicle', 'tenant_id', $request->user()->id)
            ->with('garage', 'vehicle')
            ->withSum('payments', 'amount') // kolom payments_sum_amount = sewa awal + perpanjangan
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();

        return view('tenant.rentals.index', ['rentals' => $rentals]);
    }

    /**
     * UC-10 detail satu sewa + riwayat pembayaran. Juga tujuan setelah sewa baru disimpan.
     * Sewa milik penyewa lain → 404 (tidak membocorkan bahwa sewa itu ada).
     */
    public function show(Request $request, Rental $rental): View
    {
        $rental->load('vehicle', 'garage', 'payments');
        abort_unless($rental->vehicle->tenant_id === $request->user()->id, 404);

        return view('tenant.rentals.show', ['rental' => $rental]);
    }
}
