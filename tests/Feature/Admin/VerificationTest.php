<?php

namespace Tests\Feature\Admin;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * UC-06 Verifikasi Pendaftar Baru (Terima / Tolak).
 */
class VerificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_daftar_hanya_menampilkan_pending(): void
    {
        Tenant::factory()->create(['nik' => '1111111111111111', 'status' => TenantStatus::Pending]);
        Tenant::factory()->create(['nik' => '2222222222222222', 'status' => TenantStatus::Approved]);

        $this->actingAs($this->admin)->get(route('admin.verifications.index'))
            ->assertOk()
            ->assertSee('1111111111111111')
            ->assertDontSee('2222222222222222');
    }

    public function test_daftar_kosong_menampilkan_pesan(): void
    {
        $this->actingAs($this->admin)->get(route('admin.verifications.index'))
            ->assertSee('Tidak ada pendaftar baru.');
    }

    public function test_terima_mengubah_status_approved(): void
    {
        $tenant = Tenant::factory()->create(['status' => TenantStatus::Pending]);

        $this->actingAs($this->admin)->patch(route('admin.verifications.approve', $tenant))
            ->assertRedirect(route('admin.verifications.index'));

        $this->assertSame(TenantStatus::Approved, $tenant->fresh()->status);
    }

    public function test_tolak_mengubah_status_rejected(): void
    {
        $tenant = Tenant::factory()->create(['status' => TenantStatus::Pending]);

        $this->actingAs($this->admin)->patch(route('admin.verifications.reject', $tenant))
            ->assertRedirect(route('admin.verifications.index'));

        $this->assertSame(TenantStatus::Rejected, $tenant->fresh()->status);
    }

    public function test_tenant_yang_sudah_diproses_tidak_bisa_diproses_lagi(): void
    {
        $tenant = Tenant::factory()->create(['status' => TenantStatus::Approved]);

        $this->actingAs($this->admin)->patch(route('admin.verifications.reject', $tenant))
            ->assertNotFound();

        $this->assertSame(TenantStatus::Approved, $tenant->fresh()->status);
    }

    public function test_penyewa_tidak_bisa_verifikasi(): void
    {
        $tenant = Tenant::factory()->create();
        $pending = Tenant::factory()->create(['status' => TenantStatus::Pending]);

        $this->actingAs($tenant->user)->patch(route('admin.verifications.approve', $pending))
            ->assertForbidden();
    }
}
