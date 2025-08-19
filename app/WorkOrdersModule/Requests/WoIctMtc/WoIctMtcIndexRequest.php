<?php

namespace App\WorkOrdersModule\Requests\WoIctMtc;

use Illuminate\Foundation\Http\FormRequest;

class woIctMtcIndexRequest extends FormRequest
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
            'sortBy.*.order' => 'required_with:sortBy|string|in:asc,desc',

            'search' => 'nullable|array',
            'search.name' => ['nullable', 'string', 'max:100'],
            'search.createdAt' => 'nullable|date_format:Y-m-d',
            'search.updatedAt' => 'nullable|date_format:Y-m-d',
            'search.startRange' => 'nullable|date_format:Y-m-d',
            'search.endRange' => 'nullable|date_format:Y-m-d|after_or_equal:search.startRange',
        ];
    }
}
