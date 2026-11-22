<?php

namespace App\Http\Requests;

use App\Enums\ReportType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UC-09 filter laporan: jenis laporan, tahun, dan bulan. Semua opsional (default: per bulan, tahun & bulan ini).
 */
class ReportRequest extends FormRequest
{
    /**
     * Akses sudah dibatasi middleware role:admin.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['nullable', Rule::enum(ReportType::class)],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'month' => ['nullable', 'integer', 'between:1,12'],
        ];
    }

    /**
     * Nama kolom di pesan error ("type" di file bahasa berarti tipe garasi).
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['type' => 'jenis laporan', 'year' => 'tahun', 'month' => 'bulan'];
    }

    public function reportType(): ReportType
    {
        return $this->enum('type', ReportType::class) ?? ReportType::Monthly;
    }

    public function year(): int
    {
        return $this->integer('year', today()->year);
    }

    public function month(): int
    {
        return $this->integer('month', today()->month);
    }
}
