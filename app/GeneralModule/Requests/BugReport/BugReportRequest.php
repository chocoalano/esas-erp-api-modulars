<?php

namespace App\GeneralModule\Requests\BugReport;

use Illuminate\Foundation\Http\FormRequest;

class BugReportRequest extends FormRequest
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
        // Define base rules that apply to both create and update
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'status' => 'required|boolean', // This will accept 0, 1, '0', '1', true, false
            'message' => ['required', 'string'],
            'platform' => ['required', 'in:web,android,ios'],
        ];

        // Add conditional validation for the 'image' field
        if ($this->isMethod('post')) {
            $rules['image'] = ['required', 'image', 'mimes:jpeg,jpg,png,webp,bmp,gif,svg,heic,heif', 'max:10048'];
        } elseif ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['image'] = ['sometimes', 'image', 'mimes:jpeg,jpg,png,webp,bmp,gif,svg,heic,heif', 'max:10048'];
        }

        return $rules;
    }
}
