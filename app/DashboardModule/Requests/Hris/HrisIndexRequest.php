<?php

namespace App\DashboardModule\Requests\Hris;

use Illuminate\Foundation\Http\FormRequest;

class HrisIndexRequest extends FormRequest
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
            'company_id' => 'nullable|integer|exists:companies,id',
            'start' => 'nullable|date|before_or_equal:end',
            'end' => 'nullable|date|after_or_equal:start',
        ];
    }
}
