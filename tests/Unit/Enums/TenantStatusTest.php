<?php

namespace Tests\Unit\Enums;

use App\Enums\PaymentType;
use App\Enums\RentalType;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use PHPUnit\Framework\TestCase;

class TenantStatusTest extends TestCase
{
    public function test_pesan_login_per_status(): void
    {
        $this->assertSame('Akun menunggu verifikasi admin.', TenantStatus::Pending->loginMessage());
        $this->assertSame('Pendaftaran ditolak.', TenantStatus::Rejected->loginMessage());
        $this->assertNull(TenantStatus::Approved->loginMessage());
    }

    public function test_nilai_enum_sesuai_database(): void
    {
        $this->assertSame('tenant', UserRole::Tenant->value);
        $this->assertSame('admin', UserRole::Admin->value);
        $this->assertSame('pending', TenantStatus::Pending->value);
        $this->assertSame('daily', RentalType::Daily->value);
        $this->assertSame('initial', PaymentType::Initial->value);
        $this->assertSame('extension', PaymentType::Extension->value);
    }

    public function test_label_tipe_sewa_dan_pembayaran(): void
    {
        $this->assertSame('Harian', RentalType::Daily->label());
        $this->assertSame('Bulanan', RentalType::Monthly->label());
        $this->assertSame('Sewa awal', PaymentType::Initial->label());
        $this->assertSame('Perpanjangan', PaymentType::Extension->label());
    }
}
