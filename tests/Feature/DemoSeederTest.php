<?php

namespace Tests\Feature;

use App\Models\Garage;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\Tenant;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Data demo untuk presentasi: harus lolos semua aturan & constraint, dan langsung bisa dipakai per fitur.
 */
class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-22');
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);
    }

    public function test_akun_demo_bisa_login(): void
    {
        foreach (['admin@garasi.test', 'budi@garasi.test', 'siti@garasi.test'] as $email) {
            $this->post(route('login'), ['email' => $email, 'password' => 'password'])->assertSessionHasNoErrors();
            $this->assertAuthenticated();
            $this->post(route('logout'));
        }
    }

    public function test_penyewa_dengan_semua_status(): void
    {
        $this->assertEquals(
            ['approved' => 2, 'pending' => 1, 'rejected' => 1],
            Tenant::query()->get()->countBy(fn (Tenant $tenant) => $tenant->status->value)->all(),
        );
    }

    public function test_garasi_tiap_tipe(): void
    {
        $this->assertEqualsCanonicalizing(
            ['S001', 'S002', 'S003', 'S004', 'S005', 'C001', 'C002', 'C003', 'C004', 'C005', 'E001', 'E002', 'E003'],
            Garage::all()->map(fn (Garage $garage) => $garage->code())->all(),
        );
    }

    public function test_sewa_aktif_selesai_dan_perpanjangan(): void
    {
        $this->assertSame(3, Rental::query()->activeOn(today())->count());
        $this->assertGreaterThan(0, Rental::query()->where('end_date', '<', today())->count());
        $this->assertSame(1, Payment::query()->where('type', 'extension')->count());
    }

    public function test_tanggal_selesai_sewa_sama_dengan_pembayaran_terakhir(): void
    {
        $rentals = Rental::query()->with('payments')->get();
        $this->assertNotEmpty($rentals);

        foreach ($rentals as $rental) {
            $this->assertSame($rental->payments->max('end_date')->toDateString(), $rental->end_date->toDateString());
        }
    }

    public function test_pembayaran_tersebar_di_beberapa_bulan_dan_bukti_ada(): void
    {
        $months = Payment::query()->get()->map(fn (Payment $payment) => $payment->created_at->format('Y-m'))->unique();

        $this->assertGreaterThanOrEqual(3, $months->count());
        Payment::query()->pluck('proof_path')->unique()->each(fn (string $path) => Storage::disk('public')->assertExists($path));
    }
}
