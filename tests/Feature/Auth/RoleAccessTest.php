<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pembatasan halaman per role lewat middleware EnsureRole ("role:admin" / "role:tenant").
 */
class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_tidak_bisa_buka_halaman_admin(): void
    {
        $tenant = Tenant::factory()->create();

        $this->actingAs($tenant->user)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_admin_tidak_bisa_buka_halaman_penyewa(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('tenant.dashboard'))->assertForbidden();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('home'))->assertRedirect(route('login'));
    }
}
