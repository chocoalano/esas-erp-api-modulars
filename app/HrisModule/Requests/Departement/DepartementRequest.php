<?php

namespace App\HrisModule\Requests\Departement;

use Illuminate\Foundation\Http\FormRequest;

class DepartementRequest extends FormRequest
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
        'name' => 'required|string|max:255'
        ];
    }
}
