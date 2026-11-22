<?php

namespace Tests\Feature\Auth;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * UC-01 Mendaftar sebagai Penyewa.
 */
class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Data form daftar yang valid; $override mengganti sebagian isinya.
     *
     * @param  array<string, string>  $override
     * @return array<string, string>
     */
    private function form(array $override = []): array
    {
        return array_merge([
            'name' => 'Budi Santoso',
            'nik' => '3201234567890001',
            'phone' => '081234567890',
            'email' => 'budi@example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ], $override);
    }

    public function test_daftar_membuat_user_dan_tenant_pending(): void
    {
        $response = $this->post(route('register'), $this->form());

        $response->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Pendaftaran berhasil, menunggu verifikasi admin.');
        $this->assertDatabaseHas('users', ['email' => 'budi@example.com', 'role' => 'tenant']);
        $this->assertDatabaseHas('tenants', ['nik' => '3201234567890001', 'status' => 'pending']);
        $this->assertGuest();
    }

    public function test_daftar_nik_bukan_16_digit_ditolak(): void
    {
        $this->post(route('register'), $this->form(['nik' => '320123456789000']))
            ->assertSessionHasErrors(['nik' => 'NIK harus 16 digit angka.']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_daftar_email_kembar_ditolak(): void
    {
        User::factory()->create(['email' => 'budi@example.com']);

        $this->post(route('register'), $this->form())->assertSessionHasErrors('email');
    }

    public function test_daftar_email_tanpa_titik_di_domain_ditolak(): void
    {
        $this->post(route('register'), $this->form(['email' => 'budi@gmailcom']))
            ->assertSessionHasErrors('email');
    }

    public function test_daftar_telepon_boleh_diawali_0_atau_plus62(): void
    {
        $this->post(route('register'), $this->form(['phone' => '+6281234567890']))
            ->assertSessionHasNoErrors();
    }

    public function test_daftar_telepon_bukan_angka_ditolak(): void
    {
        foreach (['abc', '12345', '81234567890', '0812-3456-7890'] as $phone) {
            $this->post(route('register'), $this->form(['phone' => $phone]))
                ->assertSessionHasErrors('phone');
        }
    }

    public function test_nik_pendaftar_yang_ditolak_tidak_bisa_dipakai_lagi(): void
    {
        Tenant::factory()->create(['nik' => '3201234567890001', 'status' => TenantStatus::Rejected]);

        $this->post(route('register'), $this->form())->assertSessionHasErrors('nik');
    }
}
