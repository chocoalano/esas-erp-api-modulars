<?php

namespace App\WorkOrdersModule\Requests\WoIctMtc;

use Illuminate\Foundation\Http\FormRequest;

class WoIctMtcRequest extends FormRequest
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
            'wo_no' => 'nullable|string|max:255',
            'division_target' => ['nullable', 'integer', 'exists:departements,id'],
            'request_date' => 'nullable|date',
            'area' => 'required|string|max:255',
            'complaint' => 'required|string|max:255',
            'asset_info' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i'
        ];
    }
}
