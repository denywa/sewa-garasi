<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GarageType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGarageRequest;
use App\Models\Garage;
use App\Services\GarageCodeGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * UC-05 Buat Garasi: admin membuat sejumlah garasi sekaligus untuk satu tipe.
 */
class GarageController extends Controller
{
    /**
     * Tampilkan form Buat Garasi, beserta kode berikutnya & sisa kuota tiap tipe
     * agar admin tahu batasnya sebelum menekan simpan.
     */
    public function create(GarageCodeGenerator $generator): View
    {
        // Nomor tertinggi per tipe, contoh: ['car' => 9]. Tipe yang belum punya garasi tidak muncul.
        $lastNumbers = Garage::query()->toBase()
            ->selectRaw('type, MAX(number) AS last_number')
            ->groupBy('type')
            ->pluck('last_number', 'type');

        $quota = [];
        foreach (GarageType::cases() as $type) {
            $last = (int) ($lastNumbers[$type->value] ?? 0);
            $quota[$type->value] = [
                'next' => $generator->format($type, $last + 1),
                'remaining' => GarageCodeGenerator::MAX_NUMBER - $last,
            ];
        }

        return view('admin.garages.create', ['types' => GarageType::cases(), 'quota' => $quota]);
    }

    /**
     * Simpan garasi baru dengan nomor melanjutkan nomor tertinggi tipe tersebut (C009 → C010, C011).
     */
    public function store(StoreGarageRequest $request, GarageCodeGenerator $generator): RedirectResponse
    {
        $type = $request->enum('type', GarageType::class);

        $numbers = DB::transaction(function () use ($request, $generator, $type) {
            // Kunci garasi tipe ini sampai transaksi selesai: dua admin yang menyimpan bersamaan
            // tidak bisa membaca nomor terakhir yang sama.
            $lastNumber = (int) Garage::query()->where('type', $type)->lockForUpdate()->max('number');

            try {
                $numbers = $generator->nextNumbers($lastNumber, $request->integer('count'));
            } catch (InvalidArgumentException $e) {
                throw ValidationException::withMessages(['count' => $e->getMessage()]);
            }

            // Satu query INSERT untuk semua baris (maks. 999), bukan satu query per garasi.
            $now = now();
            Garage::insert(array_map(fn (int $number) => [
                'type' => $type->value,
                'number' => $number,
                'daily_price' => $request->integer('daily_price'),
                'price_per_kwh' => $request->input('price_per_kwh'),
                'created_at' => $now,
                'updated_at' => $now,
            ], $numbers));

            return $numbers;
        });

        $first = $generator->format($type, $numbers[0]);
        $last = $generator->format($type, end($numbers));
        $codes = $first === $last ? $first : "{$first} – {$last}";

        return redirect()->route('admin.garages.create')->with('status', "Garasi dibuat: {$codes}.");
    }
}
