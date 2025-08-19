<?php

namespace App\WorkOrdersModule\Requests\WoIctMtc;

use App\WorkOrdersModule\Enums\WorkOrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WoIctMtcServiceRequest extends FormRequest
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
            'description' => 'required|string|max:255',
            'status' => ['required', Rule::in(array_map(fn($case) => $case->value, WorkOrderStatus::cases()))],
            'sparepart_change' => 'nullable|array|min:1',
            'sparepart_change.*.part_name' => 'required|string|min:1',
            'sparepart_change.*.quantity' => 'required|numeric|min:1',
            'sparepart_change.*.remarks' => 'required|string|min:1',
        ];
    }

}
