<?php

namespace App\WorkOrdersModule\Requests\WoIctMtc;

use App\WorkOrdersModule\Enums\WorkOrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WoIctMtcSignoffRequest extends FormRequest
{
    /**
     * Hentikan validasi di error pertama secara global.
     * (Alternatif per-field bisa pakai 'bail' di masing-masing rule.)
     */
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        // TODO: pakai policy bila siap
        // return $this->user()->can('signoff', WorkOrder::class);
        return true;
    }

    public function rules(): array
    {
        $statusValues = array_map(
            static fn ($case) => $case->value,
            WorkOrderStatus::cases()
        );

        return [
            // status diwajibkan & hanya boleh salah satu dari enum
            'status' => [
                'required',
                Rule::in($statusValues),
            ],

            // foreign keys ke users (soft delete-aware)
            'done_by_id' => [
                'sometimes', 'nullable', 'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
            ],
            'head_maintenance_id' => [
                'sometimes', 'nullable', 'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
            ],
            'requester_verify_id' => [
                'sometimes', 'nullable', 'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
                // Jika perlu cegah sama dengan pelaksana, aktifkan ini:
                // 'different:done_by_id',
            ],

            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],

            // biarkan Eloquent/Carbon mengurus parsing bila format valid
            'signed_at' => ['sometimes', 'nullable', 'date'],
            // Jika perlu paksa format spesifik:
            // 'signed_at' => ['sometimes', 'nullable', 'date_format:Y-m-d H:i:s'],
        ];
    }

    /**
     * Normalisasi ringan agar validasi lebih konsisten.
     * - '' -> null untuk kolom nullable
     * - trim notes
     * - casting integer untuk foreign key (opsional tapi rapi)
     */
    protected function prepareForValidation(): void
    {
        $payload = [];

        // Normalize IDs
        foreach (['done_by_id', 'head_maintenance_id', 'requester_verify_id'] as $key) {
            $val = $this->input($key);
            $payload[$key] = ($val === '' || $val === null) ? null : (int) $val;
        }

        // Normalize timestamp
        $signedAt = $this->input('signed_at');
        $payload['signed_at'] = ($signedAt === '') ? null : $signedAt;

        // Trim notes
        $notes = $this->input('notes');
        $payload['notes'] = is_string($notes) ? trim($notes) : $notes;

        $this->merge($payload);
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status wajib diisi.',
            'status.in'       => 'Status tidak valid.',

            'done_by_id.exists'          => 'Pengguna yang menandatangani tidak ditemukan.',
            'head_maintenance_id.exists' => 'Head Maintenance tidak valid.',
            'requester_verify_id.exists' => 'Verifikator pemohon tidak valid.',
            'requester_verify_id.different' => 'Verifikator tidak boleh sama dengan pelaksana.',

            'notes.max'      => 'Catatan maksimal :max karakter.',
            'signed_at.date' => 'Tanggal tanda tangan tidak valid.',
        ];
    }

    public function attributes(): array
    {
        return [
            'status'              => 'Status',
            'done_by_id'          => 'Dikerjakan oleh',
            'head_maintenance_id' => 'Head Maintenance',
            'requester_verify_id' => 'Verifikasi Pemohon',
            'notes'               => 'Catatan',
            'signed_at'           => 'Waktu Tanda Tangan',
        ];
    }
}
