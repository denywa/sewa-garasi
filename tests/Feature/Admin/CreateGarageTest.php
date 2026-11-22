<?php

namespace Tests\Feature\Admin;

use App\Enums\GarageType;
use App\Models\Garage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * UC-05 Buat Garasi (batch, kode otomatis melanjutkan nomor terakhir per tipe).
 */
class CreateGarageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Kirim form Buat Garasi sebagai admin.
     *
     * @param  array<string, mixed>  $data
     */
    private function store(array $data): TestResponse
    {
        $admin = User::factory()->admin()->create();

        return $this->actingAs($admin)->post(route('admin.garages.store'), $data + [
            'daily_price' => 50_000,
            'price_per_kwh' => '',
        ]);
    }

    /**
     * @return list<int>
     */
    private function numbers(GarageType $type): array
    {
        return Garage::query()->where('type', $type)->orderBy('number')->pluck('number')->all();
    }

    public function test_buat_garasi_pertama_dari_001(): void
    {
        $this->store(['type' => 'bicycle', 'count' => 3])
            ->assertRedirect(route('admin.garages.create'))
            ->assertSessionHas('status', 'Garasi dibuat: S001 – S003.');

        $this->assertSame([1, 2, 3], $this->numbers(GarageType::Bicycle));
    }

    public function test_melanjutkan_nomor_terakhir(): void
    {
        Garage::factory()->count(9)->state(new Sequence(fn (Sequence $s) => ['number' => $s->index + 1]))->create();

        $this->store(['type' => 'car', 'count' => 2])
            ->assertSessionHas('status', 'Garasi dibuat: C010 – C011.');

        $this->assertSame(range(1, 11), $this->numbers(GarageType::Car));
    }

    public function test_form_menampilkan_kode_berikutnya_dan_sisa_kuota(): void
    {
        Garage::factory()->create(['type' => GarageType::Car, 'number' => 9]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.garages.create'))
            ->assertViewHas('quota', [
                'bicycle' => ['next' => 'S001', 'remaining' => 999],
                'car' => ['next' => 'C010', 'remaining' => 990],
                'electric_car' => ['next' => 'E001', 'remaining' => 999],
            ]);
    }

    public function test_kuota_999_ditolak(): void
    {
        Garage::factory()->create(['type' => GarageType::Car, 'number' => 990]);

        $this->store(['type' => 'car', 'count' => 10])
            ->assertSessionHasErrors(['count' => 'Sisa kuota kode tipe ini: 9.']);

        $this->assertSame([990], $this->numbers(GarageType::Car));
    }

    public function test_jumlah_di_luar_1_sampai_999_ditolak(): void
    {
        foreach ([0, 1000] as $count) {
            $this->store(['type' => 'car', 'count' => $count])->assertSessionHasErrors('count');
        }

        $this->assertDatabaseCount('garages', 0);
    }

    public function test_garasi_listrik_wajib_harga_kwh(): void
    {
        $this->store(['type' => 'electric_car', 'count' => 1])->assertSessionHasErrors('price_per_kwh');
    }

    public function test_garasi_mobil_tidak_boleh_harga_kwh(): void
    {
        $this->store(['type' => 'car', 'count' => 1, 'price_per_kwh' => 2_500])->assertSessionHasErrors('price_per_kwh');
    }

    public function test_garasi_listrik_menyimpan_harga_kwh(): void
    {
        $this->store(['type' => 'electric_car', 'count' => 1, 'price_per_kwh' => 2_500])
            ->assertSessionHas('status', 'Garasi dibuat: E001.');

        $this->assertDatabaseHas('garages', ['type' => 'electric_car', 'number' => 1, 'daily_price' => 50_000, 'price_per_kwh' => 2_500]);
    }
}
