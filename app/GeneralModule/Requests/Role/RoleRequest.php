<?php

namespace App\GeneralModule\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;

class RoleRequest extends FormRequest
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
        $id = $this->route('id') ?? null;
        return [
            'name' => ['required', 'string', 'max:20', 'unique:roles,name,' . $id],
            'user_id' => ['required', 'array'],
            'user_id.*' => ['integer', 'exists:users,id'],
            'permission' => ['required', 'array'],
            'permission.*' => ['integer', 'exists:permissions,id'],
        ];
    }
}
