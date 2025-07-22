<?php

namespace App\HrisModule\Requests\UserAttendance;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceRequest extends FormRequest
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
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'time_id' => ['required', 'integer', 'exists:time_workes,id'],
            'time' => [
                'required',
                'date_format:H:i:s'
            ],
            'lat' => [
                'required',
                'numeric',
                'regex:/^(\-?\d+(\.\d+)?)$/',
                'between:-90,90',
            ],
            'long' => [
                'required',
                'numeric',
                'regex:/^(\-?\d+(\.\d+)?)$/',
                'between:-180,180',
            ],
            'type' => [
                'required',
                'string',
                'in:in,out',
            ],
            'image' => [
                'required',
                'image',
                'mimes:jpeg,png,jpg'
            ],
        ];
    }
}
