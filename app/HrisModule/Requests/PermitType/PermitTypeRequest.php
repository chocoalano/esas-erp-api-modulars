<?php

namespace App\HrisModule\Requests\PermitType;

use Illuminate\Foundation\Http\FormRequest;

class PermitTypeRequest extends FormRequest
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
        'type' => 'required|string|max:255',
        'is_payed' => 'required|boolean',
        'approve_line' => 'required|boolean',
        'approve_manager' => 'required|boolean',
        'approve_hr' => 'required|boolean',
        'with_file' => 'nullable|boolean',
        'show_mobile' => 'required|boolean'
        ];
    }
}
