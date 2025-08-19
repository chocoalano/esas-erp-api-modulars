<?php

namespace App\WorkOrdersModule\Requests\WoDesign;

use App\WorkOrdersModule\Enums\DesignRequestPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class woDesignRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // HEADER
            'request_no' => ['nullable', 'string', 'max:50',],
            'request_date' => ['nullable', 'date'],
            'need_by_date' => ['nullable', 'date', 'after_or_equal:request_date'],
            'priority' => ['required', Rule::in('high', 'medium', 'low')],

            // sesuai migration: pic_id required, yang lain nullable
            'pic_id' => ['nullable', 'integer', 'exists:users,id'],
            'division_id' => ['nullable', 'integer', 'exists:departements,id'],
            'submitted_to_id' => ['required', 'integer', 'exists:users,id'],
            'acknowledged_by_id' => ['nullable', 'integer', 'exists:users,id'],

            'notes' => ['nullable', 'string'], // text di DB, tak perlu max 255

            // ITEMS (DETAIL)
            'items' => ['required', 'array', 'min:1'],
            'items.*.line_no' => ['required', 'integer', 'min:1', 'distinct'],
            'items.*.kebutuhan' => ['required', 'string'],
            'items.*.isi_konten' => ['required', 'string'],
            'items.*.ukuran' => ['required', 'string', 'max:100'],
            'items.*.referensi' => ['nullable', 'string'],
            'items.*.keterangan' => ['nullable', 'string'],
        ];
    }
}
