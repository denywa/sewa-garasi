<?php

namespace App\Enums;

/**
 * Jenis akun yang bisa login: admin atau penyewa (tenant).
 */
enum UserRole: string
{
    case Admin = 'admin';
    case Tenant = 'tenant';
}
