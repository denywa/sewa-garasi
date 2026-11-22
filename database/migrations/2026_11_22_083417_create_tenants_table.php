<?php

use App\Enums\TenantStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Data khusus penyewa, 1:1 dengan users (shared primary key: user_id = PK sekaligus FK).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()
                ->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->char('nik', 16)->unique();
            $table->string('phone', 20);
            $table->enum('status', array_column(TenantStatus::cases(), 'value'))
                ->default(TenantStatus::Pending->value);
            $table->timestamps();
        });

        DB::statement("ALTER TABLE tenants ADD CONSTRAINT tenants_nik_check CHECK (nik REGEXP '^[0-9]{16}$')");
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
