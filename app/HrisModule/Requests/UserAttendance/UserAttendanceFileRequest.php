<?php

namespace App\HrisModule\Requests\UserAttendance;

use Illuminate\Foundation\Http\FormRequest;

class UserAttendanceFileRequest extends FormRequest
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
            'name' => 'nullable|string|max:255',
            'createdAt' => 'nullable|date',
            'updatedAt' => 'nullable|date',
            'start' => 'nullable|date',
            'end' => 'nullable|date|after_or_equal:startRange',
        ];
    }
}
