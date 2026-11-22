<?php

namespace App\Enums;

/**
 * Status verifikasi pendaftaran penyewa oleh admin.
 */
enum TenantStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    /**
     * Pesan penolakan login untuk status ini, atau null bila penyewa boleh login.
     */
    public function loginMessage(): ?string
    {
        return match ($this) {
            self::Pending => 'Akun menunggu verifikasi admin.',
            self::Rejected => 'Pendaftaran ditolak.',
            self::Approved => null,
        };
    }
}
