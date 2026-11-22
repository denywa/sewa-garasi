<?php

namespace Tests\Feature\Auth;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * UC-02 Login dan UC-03 Logout. Password semua user dari factory = "password".
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    private function login(User $user, string $password = 'password'): TestResponse
    {
        return $this->post(route('login'), ['email' => $user->email, 'password' => $password]);
    }

    public function test_tenant_pending_tidak_bisa_login(): void
    {
        $tenant = Tenant::factory()->create(['status' => TenantStatus::Pending]);

        $this->login($tenant->user)->assertSessionHasErrors(['email' => 'Akun menunggu verifikasi admin.']);
        $this->assertGuest();
    }

    public function test_tenant_rejected_tidak_bisa_login(): void
    {
        $tenant = Tenant::factory()->create(['status' => TenantStatus::Rejected]);

        $this->login($tenant->user)->assertSessionHasErrors(['email' => 'Pendaftaran ditolak.']);
        $this->assertGuest();
    }

    public function test_tenant_approved_login_ke_dashboard_penyewa(): void
    {
        $tenant = Tenant::factory()->create();

        $this->login($tenant->user)->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($tenant->user);
        $this->get(route('home'))->assertRedirect(route('tenant.dashboard'));
    }

    public function test_admin_login_ke_dashboard_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->login($admin)->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($admin);
        $this->get(route('home'))->assertRedirect(route('admin.dashboard'));
    }

    public function test_password_salah(): void
    {
        $admin = User::factory()->admin()->create();

        $this->login($admin, 'salah123')->assertSessionHasErrors(['email' => 'Email atau password salah.']);
        $this->assertGuest();
    }

    public function test_logout(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
