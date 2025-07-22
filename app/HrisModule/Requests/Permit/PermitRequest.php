<?php

namespace App\HrisModule\Requests\Permit;

use Illuminate\Foundation\Http\FormRequest;

class PermitRequest extends FormRequest
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
        $id = $this->route('id') ?? null;
        return [
            'company_id' => 'required|exists:companies,id',
            'departement_id' => 'required|exists:departements,id',
            'user_id' => 'required|exists:users,id',
            'permit_type_id' => 'required|exists:permit_types,id',
            'user_timework_schedule_id' => 'required|exists:user_timework_schedules,id',
            'permit_numbers' => 'required|string|max:50|unique:permits,permit_numbers,' . $id,
            'timein_adjust' => ['nullable', 'date_format:H:i'],
            'timeout_adjust' => ['nullable', 'date_format:H:i', 'after:timein_adjust'],
            'current_shift_id' => 'nullable|exists:time_workes,id',
            'adjust_shift_id' => 'nullable|exists:time_workes,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'required|regex:/^\d{2}:\d{2}(:\d{2})?$/',
            'end_time' => 'required|regex:/^\d{2}:\d{2}(:\d{2})?$/',
            'notes' => 'nullable|string|max:255',
            'file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10048',
        ];
    }
}
