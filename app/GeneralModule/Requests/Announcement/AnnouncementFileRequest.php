<?php

namespace App\GeneralModule\Requests\Announcement;

use Illuminate\Foundation\Http\FormRequest;

class AnnouncementFileRequest extends FormRequest
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
            'title' => 'nullable|string|max:255',
            'createdAt' => 'nullable|date',
            'updatedAt' => 'nullable|date',
            'startRange' => 'nullable|date',
            'endRange' => 'nullable|date|after_or_equal:startRange',
        ];
    }
}
