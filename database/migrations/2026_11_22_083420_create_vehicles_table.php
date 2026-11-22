<?php

use App\Enums\VehicleType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kendaraan milik penyewa. Plat disimpan sudah dinormalisasi (lihat Vehicle::normalizePlate).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')
                ->constrained('tenants', 'user_id')->restrictOnDelete()->cascadeOnUpdate();
            $table->enum('type', array_column(VehicleType::cases(), 'value'));
            $table->string('plate_number', 15)->nullable()->unique();
            $table->string('brand_model', 100);
            $table->decimal('battery_capacity_kwh', 6, 2)->nullable();
            $table->timestamps();
        });

        // Selain sepeda wajib berplat; kapasitas baterai diisi jika dan hanya jika mobil listrik.
        DB::statement("ALTER TABLE vehicles ADD CONSTRAINT vehicles_plate_check
            CHECK (type = 'bicycle' OR plate_number IS NOT NULL)");
        DB::statement("ALTER TABLE vehicles ADD CONSTRAINT vehicles_battery_check
            CHECK ((type = 'electric_car') = (battery_capacity_kwh IS NOT NULL))");
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
