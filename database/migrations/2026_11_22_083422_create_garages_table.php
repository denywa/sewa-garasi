<?php

use App\Enums\GarageType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Garasi. Kode (S001, C010) tidak disimpan: dibentuk dari prefix tipe + number.
 * Status tersedia/used tidak disimpan: dihitung dari rentals.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('garages', function (Blueprint $table) {
            $table->id();
            $table->enum('type', array_column(GarageType::cases(), 'value'));
            $table->unsignedSmallInteger('number');
            $table->unsignedInteger('daily_price');
            $table->unsignedInteger('price_per_kwh')->nullable();
            $table->timestamps();

            $table->unique(['type', 'number']);
        });

        DB::statement('ALTER TABLE garages ADD CONSTRAINT garages_number_check CHECK (number BETWEEN 1 AND 999)');
        DB::statement('ALTER TABLE garages ADD CONSTRAINT garages_daily_price_check CHECK (daily_price > 0)');
        // Harga per kWh diisi jika dan hanya jika garasi mobil listrik.
        DB::statement("ALTER TABLE garages ADD CONSTRAINT garages_price_per_kwh_check
            CHECK ((type = 'electric_car') = (price_per_kwh IS NOT NULL))");
    }

    public function down(): void
    {
        Schema::dropIfExists('garages');
    }
};
