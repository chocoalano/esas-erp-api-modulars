<?php

namespace App\GeneralModule\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class AuthUpdatePasswordRequest extends FormRequest
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
            'password' => [
                'required',
                'string',
                'min:8',
            ],
            'new_password' => [
                'required',
                'string',
                'min:8',
                'different:password', // Pastikan password baru berbeda dari password lama
            ],
            'confirmation_new_password' => [
                'required',
                'string',
                'min:8',
                'same:new_password', // Pastikan konfirmasi password baru cocok dengan password baru
            ],
        ];
    }
}
