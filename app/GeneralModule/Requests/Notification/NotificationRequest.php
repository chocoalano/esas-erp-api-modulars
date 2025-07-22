<?php

namespace App\GeneralModule\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;

class NotificationRequest extends FormRequest
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
        'type' => 'required|string|max:255',
        'notifiable_type' => 'required|string|max:255',
        'notifiable_id' => 'required|integer|exists:related_table,id',
        'data' => 'required|date_format:H:i',
        'read_at' => 'required|date_format:H:i'
        ];
    }
}
