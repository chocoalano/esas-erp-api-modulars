<?php

namespace App\HrisModule\Requests\TimeWorke;

use Illuminate\Foundation\Http\FormRequest;

class TimeWorkeRequest extends FormRequest
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
            'company_id' => 'required|integer|exists:companies,id',
            'departemen_id' => 'required|integer|exists:departements,id',
            'name' => 'required|string|max:255',
            'in' => ['required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'out' => ['required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],

        ];
    }
}
