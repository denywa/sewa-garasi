<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExtendRentalRequest;
use App\Http\Requests\ExtensionQuoteRequest;
use App\Models\Rental;
use App\Services\RentalCalculator;
use App\Services\RentalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * UC-07 Perpanjang Status Garasi: pilih sewa aktif → isi tanggal selesai baru → hitung sisa biaya → upload bukti.
 */
class ExtensionController extends Controller
{
    /**
     * Daftar garasi yang sedang disewa hari ini (tanggal mulai ≤ hari ini ≤ tanggal selesai).
     */
    public function index(): View
    {
        $rentals = Rental::query()
            ->activeOn(today())
            ->with('garage', 'vehicle.tenant.user')
            ->orderBy('end_date')
            ->get();

        return view('admin.extensions.index', ['rentals' => $rentals]);
    }

    /**
     * Form tanggal selesai baru. Bila tanggal sudah diisi (query new_end_date), tampilkan sisa biaya.
     */
    public function edit(ExtensionQuoteRequest $request, Rental $rental, RentalCalculator $calculator): View
    {
        $rental->load('garage', 'vehicle.tenant.user');
        $newEnd = $request->filled('new_end_date') ? Carbon::parse($request->input('new_end_date')) : null;

        return view('admin.extensions.edit', [
            'rental' => $rental,
            'active' => Rental::query()->activeOn(today())->whereKey($rental->id)->exists(),
            'newEnd' => $newEnd,
            'days' => $newEnd ? (int) $rental->end_date->diffInDays($newEnd) : null,
            'cost' => $newEnd ? $calculator->extensionCost($rental->end_date, $newEnd, $rental->garage->daily_price) : null,
        ]);
    }

    /**
     * Simpan perpanjangan lewat RentalService. Penolakan dari service tampil sebagai error form.
     */
    public function update(ExtendRentalRequest $request, Rental $rental, RentalService $service): RedirectResponse
    {
        $payment = $service->extend($rental, Carbon::parse($request->input('new_end_date')), $request->file('proof'));

        return redirect()->route('admin.extensions.index')->with('status',
            "Sewa garasi {$rental->garage->code()} diperpanjang sampai {$payment->end_date->translatedFormat('d M Y')}.");
    }
}
