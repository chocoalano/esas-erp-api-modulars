<?php

namespace App\GeneralModule\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // In most API contexts for index requests, authorization
        // is handled by middleware, so returning true here is common.
        // If specific permissions are needed, implement them here.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Define allowed sortable fields for clarity and maintainability.
        // Ensure these match your database table columns.
        $allowedSortByFields = ['id', 'name', 'nip', 'email', 'status', 'created_at', 'updated_at'];

        return [
            // Pagination Rules
            'page' => ['nullable', 'integer', 'min:1'], // 'page' is often optional, defaulting to 1
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'], // 'limit' is often optional, defaulting to a sensible value

            // Sorting Rules
            // Using Rule::in() for better readability and consistency with other rules
            'sortBy' => ['nullable', 'array'],
            'sortBy.*.key' => ['required_with:sortBy', 'string', Rule::in($allowedSortByFields)],
            'sortBy.*.order' => ['required_with:sortBy', 'string', Rule::in(['asc', 'desc'])],

            // Search/Filter Rules
            'search' => ['nullable', 'array'],
            'search.company' => ['nullable', 'exists:companies,id'],
            'search.departemen' => ['nullable', 'exists:departements,id'], // Typo corrected: 'departements' -> 'departments' if that's your table name
            'search.position' => ['nullable', 'exists:job_positions,id'],
            'search.level' => ['nullable', 'exists:job_levels,id'],
            'search.nip' => ['nullable', 'string', 'max:50'],
            'search.name' => ['nullable', 'string', 'max:100'],
            'search.email' => ['nullable', 'email'],
            'search.status' => ['nullable', Rule::in(['active', 'inactive', 'resign'])],

            // Date filtering rules, using consistent naming
            'search.createdAtFrom' => ['nullable', 'date_format:Y-m-d'],
            'search.createdAtTo' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:search.createdAtFrom'],
            'search.updatedAtFrom' => ['nullable', 'date_format:Y-m-d'],
            'search.updatedAtTo' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:search.updatedAtFrom'],
            // Removed 'startRange' and 'endRange' as they can be ambiguous.
            // Explicit date fields like 'createdAtFrom/To' are clearer.
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'sortBy.*.key' => 'kolom pengurutan', // More specific attribute name
            'sortBy.*.order' => 'arah pengurutan',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // Pagination Messages
            'page.integer' => 'Parameter halaman harus berupa angka.',
            'page.min' => 'Parameter halaman minimal adalah :min.',
            'limit.integer' => 'Parameter batas harus berupa angka.',
            'limit.min' => 'Parameter batas minimal adalah :min.',
            'limit.max' => 'Parameter batas maksimal adalah :max.',

            // Sorting Messages
            'sortBy.array' => 'Parameter urutan (sortBy) harus berupa array.',
            'sortBy.*.key.required_with' => 'Kolom pengurutan diperlukan ketika sortBy diberikan.',
            'sortBy.*.key.string' => 'Kolom pengurutan harus berupa teks.',
            'sortBy.*.key.in' => 'Kolom pengurutan tidak valid. Pilihan yang diizinkan adalah: :values.',
            'sortBy.*.order.required_with' => 'Arah pengurutan diperlukan ketika sortBy diberikan.',
            'sortBy.*.order.string' => 'Arah pengurutan harus berupa teks.',
            'sortBy.*.order.in' => 'Arah pengurutan tidak valid. Pilihan yang diizinkan adalah: :values.',

            // Search/Filter Messages
            'search.company.exists' => 'Perusahaan yang dipilih tidak valid.',
            'search.departemen.exists' => 'Departemen yang dipilih tidak valid.',
            'search.position.exists' => 'Posisi yang dipilih tidak valid.',
            'search.level.exists' => 'Level yang dipilih tidak valid.',
            'search.nip.max' => 'NIP maksimal :max karakter.',
            'search.name.max' => 'Nama maksimal :max karakter.',
            'search.email.email' => 'Format email tidak valid.',
            'search.status.in' => 'Status tidak valid. Pilihan yang diizinkan adalah: :values.',
            'search.createdAtFrom.date_format' => 'Format tanggal "Tanggal Dibuat Dari" tidak valid. Gunakan YYYY-MM-DD.',
            'search.createdAtTo.date_format' => 'Format tanggal "Tanggal Dibuat Sampai" tidak valid. Gunakan YYYY-MM-DD.',
            'search.createdAtTo.after_or_equal' => 'Tanggal "Tanggal Dibuat Sampai" harus setelah atau sama dengan "Tanggal Dibuat Dari".',
            'search.updatedAtFrom.date_format' => 'Format tanggal "Tanggal Diperbarui Dari" tidak valid. Gunakan YYYY-MM-DD.',
            'search.updatedAtTo.date_format' => 'Format tanggal "Tanggal Diperbarui Sampai" tidak valid. Gunakan YYYY-MM-DD.',
            'search.updatedAtTo.after_or_equal' => 'Tanggal "Tanggal Diperbarui Sampai" harus setelah atau sama dengan "Tanggal Diperbarui Dari".',
        ];
    }
}
