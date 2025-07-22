<?php

namespace App\HrisModule\Requests\UserAttendance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserAttendanceIndexRequest extends FormRequest
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
            'page' => 'required|integer|min:1',
            'limit' => 'required|integer|min:1|max:100',

            'sortBy' => 'nullable|array',
            'sortBy.*.key' => 'required_with:sortBy|string',
            'sortBy.*.order' => ['required_with:sortBy', 'string', Rule::in(['asc', 'desc'])],

            'search' => 'nullable|array',
            'search.company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'search.departement_id' => ['nullable', 'integer', 'exists:departements,id'],
            'search.user_id' => ['nullable', 'integer', 'exists:users,id'],

            'search.status_in' => ['nullable', Rule::in(['late', 'unlate', 'normal'])],
            'search.status_out' => ['nullable', Rule::in(['late', 'unlate', 'normal'])],

            'search.createdAt' => 'nullable|date_format:Y-m-d',
            'search.updatedAt' => 'nullable|date_format:Y-m-d',
            'search.start' => 'nullable|date_format:Y-m-d',
            'search.end' => 'nullable|date_format:Y-m-d|after_or_equal:search.startRange',
        ];
    }
}
