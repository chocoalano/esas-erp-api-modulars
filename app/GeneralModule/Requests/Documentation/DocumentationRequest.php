<?php

namespace App\GeneralModule\Requests\Documentation;

use Illuminate\Foundation\Http\FormRequest;

class DocumentationRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255', 'unique:documentations,title,' . $id],
            'subtitle' => ['required', 'string', 'max:255'],
            'text_docs' => ['required', 'string'],
            'status' => ['required', 'boolean'],
        ];
    }
}
