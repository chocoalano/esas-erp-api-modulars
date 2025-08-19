<?php

namespace App\WorkOrdersModule\Requests\WoDesign;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\WorkOrdersModule\Enums\DesignApprovalStatus;

class WoApprovalDesignRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Bisa tambahkan policy di sini
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in(array_map(fn($case) => $case->value, DesignApprovalStatus::cases())),
            ],
            'approved_by_id' => [
                'required',
                'exists:users,id', // pastikan user ada
            ],
            'approval_notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status approval wajib diisi.',
            'status.in' => 'Status approval tidak valid.',
            'approved_by_id.required' => 'ID approver wajib diisi.',
            'approved_by_id.exists' => 'Approver tidak ditemukan di sistem.',
            'approved_at.date' => 'Format tanggal approval tidak valid.',
            'approval_notes.max' => 'Catatan approval maksimal 1000 karakter.',
        ];
    }
}
