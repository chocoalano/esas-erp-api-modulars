<?php

namespace App\GeneralModule\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class CompanyRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            // Latitude seharusnya numeric, bukan time
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            // Longitude seharusnya numeric
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            // Radius lebih baik numeric dan minimal 1
            'radius' => ['required', 'numeric', 'min:1'],
            'full_address' => ['nullable', 'string', 'max:500'],
        ];
    }
}
