<?php

namespace Tests\Feature\Admin;

use App\Enums\PaymentType;
use App\Models\Garage;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * UC-07 Perpanjang Status Garasi dan UC-08 Upload Bukti Bayar (oleh admin).
 */
class ExtensionTest extends TestCase
{
    use RefreshDatabase;

    private Rental $rental;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-22');
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());

        // Sewa aktif 21–23 Nov di garasi C001 Rp10.000/hari, sudah ada pembayaran awal.
        $garage = Garage::factory()->create(['number' => 1, 'daily_price' => 10_000]);
        $this->rental = Rental::factory()->for($garage)->create(['start_date' => '2026-11-21', 'end_date' => '2026-11-23']);
        Payment::factory()->for($this->rental)->create(['end_date' => '2026-11-23']);
    }

    private function extend(Rental $rental, string $newEnd, ?UploadedFile $proof = null): TestResponse
    {
        return $this->from(route('admin.extensions.edit', $rental))->put(route('admin.extensions.update', $rental), [
            'new_end_date' => $newEnd,
            'proof' => $proof ?? UploadedFile::fake()->image('bukti.jpg'),
        ]);
    }

    public function test_daftar_hanya_sewa_aktif(): void
    {
        $done = Garage::factory()->create(['number' => 2]);
        Rental::factory()->for($done)->create(['start_date' => '2026-11-18', 'end_date' => '2026-11-21']);

        $this->get(route('admin.extensions.index'))->assertOk()
            ->assertSee('C001')
            ->assertDontSee('C002');
    }

    public function test_tidak_ada_sewa_aktif(): void
    {
        $this->rental->update(['end_date' => '2026-11-21']);

        $this->get(route('admin.extensions.index'))->assertSee('Tidak ada garasi yang sedang disewa hari ini.');
    }

    public function test_langkah_1_isi_tanggal(): void
    {
        $this->get(route('admin.extensions.edit', $this->rental))->assertOk()
            ->assertSee('Harus setelah 23 Nov 2026')
            ->assertSee('Hitung Sisa Biaya')
            ->assertDontSee('Simpan Perpanjangan');
    }

    public function test_hitung_sisa_biaya(): void
    {
        // Langkah 2: tanggal tidak bisa diubah di sini (harus kembali lewat "Ubah tanggal").
        $this->get(route('admin.extensions.edit', [$this->rental, 'new_end_date' => '2026-11-26']))->assertOk()
            ->assertSeeInOrder(['Tanggal selesai baru', '26 Nov 2026', 'Ubah tanggal'])
            // Rupiah memakai spasi tak terputus (NBSP) setelah "Rp".
            ->assertSeeInOrder(['3 hari', "Rp\u{00A0}10.000", "Rp\u{00A0}30.000"])
            ->assertSee('Simpan Perpanjangan')
            ->assertDontSee('Hitung Sisa Biaya');
    }

    public function test_hitung_dengan_tanggal_tidak_setelah_tanggal_lama_ditolak(): void
    {
        $this->from(route('admin.extensions.edit', $this->rental))
            ->get(route('admin.extensions.edit', [$this->rental, 'new_end_date' => '2026-11-23']))
            ->assertRedirect(route('admin.extensions.edit', $this->rental))
            ->assertSessionHasErrors(['new_end_date' => 'Tanggal selesai baru harus setelah 23-11-2026.']);
    }

    public function test_perpanjang_menyimpan_tanggal_dan_pembayaran(): void
    {
        $this->extend($this->rental, '2026-11-26')
            ->assertRedirect(route('admin.extensions.index'))
            ->assertSessionHas('status', 'Sewa garasi C001 diperpanjang sampai 26 Nov 2026.');

        $this->assertSame('2026-11-26', $this->rental->fresh()->end_date->toDateString());

        $payments = $this->rental->payments()->orderBy('id')->get();
        $this->assertSame([PaymentType::Initial, PaymentType::Extension], $payments->pluck('type')->all());
        $this->assertSame('2026-11-23', $payments[0]->end_date->toDateString());
        $this->assertSame(30_000, $payments[1]->amount);
        $this->assertSame('2026-11-26', $payments[1]->end_date->toDateString());
        Storage::disk('public')->assertExists($payments[1]->proof_path);
    }

    public function test_tanggal_baru_tidak_setelah_tanggal_lama_ditolak(): void
    {
        $this->extend($this->rental, '2026-11-23')
            ->assertSessionHasErrors(['new_end_date' => 'Tanggal selesai baru harus setelah 23-11-2026.']);

        $this->assertSame(1, $this->rental->payments()->count());
    }

    public function test_sewa_yang_sudah_selesai_tidak_bisa_diperpanjang(): void
    {
        $this->rental->update(['end_date' => '2026-11-21']);

        $this->extend($this->rental, '2026-11-26')
            ->assertSessionHasErrors(['rental' => 'Sewa ini sudah tidak aktif.']);

        $this->assertSame('2026-11-21', $this->rental->fresh()->end_date->toDateString());
    }

    public function test_bukti_wajib_gambar(): void
    {
        $this->extend($this->rental, '2026-11-26', UploadedFile::fake()->create('a.pdf', 100))
            ->assertSessionHasErrors(['proof' => 'Bukti pembayaran harus berupa gambar.']);
    }
}
