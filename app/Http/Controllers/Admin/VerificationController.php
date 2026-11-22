<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TenantStatus;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * UC-06 Verifikasi Pendaftar Baru: admin menerima / menolak penyewa berstatus pending
 * setelah mengecek KTP (di luar sistem).
 */
class VerificationController extends Controller
{
    /**
     * Daftar pendaftar pending, yang paling lama mendaftar di atas.
     */
    public function index(): View
    {
        $tenants = Tenant::query()
            ->with('user') // ambil nama & email sekaligus (hindari query per baris / N+1)
            ->where('status', TenantStatus::Pending)
            ->oldest()
            ->get();

        return view('admin.verifications.index', ['tenants' => $tenants]);
    }

    /**
     * Terima pendaftar: penyewa bisa login.
     */
    public function approve(Tenant $tenant): RedirectResponse
    {
        return $this->decide($tenant, TenantStatus::Approved, 'diterima');
    }

    /**
     * Tolak pendaftar: final, NIK & email tidak bisa dipakai daftar lagi.
     */
    public function reject(Tenant $tenant): RedirectResponse
    {
        return $this->decide($tenant, TenantStatus::Rejected, 'ditolak');
    }

    /**
     * Ubah status pendaftar pending. Pendaftar yang sudah diproses → 404.
     */
    private function decide(Tenant $tenant, TenantStatus $status, string $verb): RedirectResponse
    {
        abort_unless($tenant->status === TenantStatus::Pending, 404);

        $tenant->update(['status' => $status]);

        return redirect()->route('admin.verifications.index')
            ->with('status', "Pendaftar {$tenant->user->name} {$verb}.");
    }
}
