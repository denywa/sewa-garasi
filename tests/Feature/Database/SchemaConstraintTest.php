<?php

namespace Tests\Feature\Database;

use App\Enums\GarageType;
use App\Enums\VehicleType;
use App\Models\Garage;
use App\Models\Rental;
use App\Models\Tenant;
use App\Models\Vehicle;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Integrity constraint di database (CHECK, UNIQUE, FK RESTRICT) menolak data tidak valid
 * walaupun aplikasi lupa memvalidasi.
 */
class SchemaConstraintTest extends TestCase
{
    use RefreshDatabase;

    public function test_nik_harus_16_digit(): void
    {
        $this->expectException(QueryException::class);

        Tenant::factory()->create(['nik' => '12345']);
    }

    public function test_mobil_wajib_plat(): void
    {
        $tenant = Tenant::factory()->create();

        $this->expectException(QueryException::class);

        Vehicle::factory()->for($tenant)->create(['type' => VehicleType::Car, 'plate_number' => null]);
    }

    public function test_garasi_listrik_wajib_harga_kwh(): void
    {
        $this->expectException(QueryException::class);

        Garage::factory()->create(['type' => GarageType::ElectricCar, 'price_per_kwh' => null]);
    }

    public function test_garasi_mobil_tidak_boleh_harga_kwh(): void
    {
        $this->expectException(QueryException::class);

        Garage::factory()->create(['type' => GarageType::Car, 'price_per_kwh' => 2_000]);
    }

    public function test_nomor_garasi_kembar_ditolak(): void
    {
        Garage::factory()->create(['type' => GarageType::Car, 'number' => 1]);

        $this->expectException(QueryException::class);

        Garage::factory()->create(['type' => GarageType::Car, 'number' => 1]);
    }

    public function test_hapus_garasi_yang_punya_sewa_ditolak(): void
    {
        $rental = Rental::factory()->create();

        $this->expectException(QueryException::class);

        $rental->garage->delete();
    }
}
