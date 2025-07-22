<?php

namespace App\HrisModule\Requests\UserAttendance;

use Illuminate\Foundation\Http\FormRequest;

class UserAttendanceRequest extends FormRequest
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
        $id = $this->route('id'); // ambil ID dari route jika ada

        return [
            'user_id' => 'required|integer|exists:users,id',
            // 'user_timework_schedule_id' => 'required|integer|exists:user_timework_schedules,id',
            'user_timework_schedule_id' => 'required|integer',
            'time_in' => 'required|regex:/^\d{2}:\d{2}(:\d{2})?$/',
            'time_out' => 'required|regex:/^\d{2}:\d{2}(:\d{2})?$/',
            'type_in' => 'required|string|max:255',
            'type_out' => 'required|string|max:255',
            'lat_in' => 'required|numeric',
            'lat_out' => 'required|numeric',
            'long_in' => 'required|numeric',
            'long_out' => 'required|numeric',
            'image_in' => 'nullable|image|mimes:jpeg,png,jpg,svg,webp|max:5048',
            'image_out' => 'nullable|image|mimes:jpeg,png,jpg,svg,webp|max:5048',
            'status_in' => 'required|string|max:255',
            'status_out' => 'required|string|max:255',
            'created_by' => 'nullable|integer|exists:users,id',
            'updated_by' => 'nullable|integer|exists:users,id',
        ];
    }

    /**
     * Customizing messages if needed.
     */
    public function messages(): array
    {
        return [
            'user_id.exists' => 'User yang dipilih tidak ditemukan.',
            'user_timework_schedule_id.exists' => 'Jadwal kerja yang dipilih tidak ditemukan.',
            'lat_in.numeric' => 'Latitude check-in harus berupa angka.',
            'lat_out.numeric' => 'Latitude check-out harus berupa angka.',
            'long_in.numeric' => 'Longitude check-in harus berupa angka.',
            'long_out.numeric' => 'Longitude check-out harus berupa angka.',
        ];
    }
}
