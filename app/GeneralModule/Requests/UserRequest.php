<?php

namespace App\GeneralModule\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
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
     */
    public function rules(): array
    {
        $routeUser = $this->route('users');
        $userId = null;

        if ($routeUser instanceof \App\Models\User) {
            $userId = $routeUser->id;
        } elseif ($this->route('id')) {
            $userId = $this->route('id');
        } else {
            $userId = Auth::id(); // fallback ke user yang sedang login
        }

        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],

            'nip' => [
                'required',
                'string',
                'max:50',
                Rule::unique('users', 'nip')->ignore($userId),
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],

            'password' => [$this->isMethod('post') ? 'required' : 'nullable', 'string', 'max:20'],

            'status' => ['required', 'in:active,inactive,resign'],

            'details.phone' => [
                'required',
                'string',
                'max:20',
                Rule::unique('user_details', 'phone')->ignore($userId, 'user_id'),
            ],
            'details.placebirth' => ['required', 'string', 'max:100'],
            'details.datebirth' => ['required', 'date'],
            'details.gender' => ['required', 'in:m,w'],
            'details.blood' => ['required', 'in:a,b,ab,o'],
            'details.marital_status' => ['required', 'in:single,married,widow,widower'],
            'details.religion' => ['required', 'in:islam,protestan,khatolik,hindu,buddha,khonghucu'],

            'address.identity_type' => ['required', 'in:ktp,sim,passport'],
            'address.identity_numbers' => ['required', 'string', 'max:100'],
            'address.province' => ['required', 'string', 'max:100'],
            'address.city' => ['required', 'string', 'max:100'],
            'address.citizen_address' => ['required', 'string', 'max:255'],
            'address.residential_address' => ['required', 'string', 'max:255'],

            'salaries.basic_salary' => ['required', 'numeric', 'min:0'],
            'salaries.payment_type' => ['required', 'in:Monthly,Weekly,Daily'],

            'employee.departement_id' => ['required', 'integer', 'exists:departements,id'],
            'employee.job_position_id' => ['required', 'integer', 'exists:job_positions,id'],
            'employee.job_level_id' => ['required', 'integer', 'exists:job_levels,id'],
            'employee.approval_line_id' => ['required', 'integer', 'exists:users,id'],
            'employee.approval_manager_id' => ['required', 'integer', 'exists:users,id'],
            'employee.join_date' => ['required', 'date'],
            'employee.sign_date' => ['required', 'date'],
            'employee.bank_name' => ['required', 'string', 'max:100'],
            'employee.bank_number' => ['required', 'string', 'max:30'],
            'employee.bank_holder' => ['required', 'string', 'max:100'],

            'avatar_file' => [
                'nullable',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp,heic,heif',
                'max:10048',
            ],
        ];
    }
}
