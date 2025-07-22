<?php

namespace App\HrisModule\Requests\TimeUserSchedule;

use Illuminate\Foundation\Http\FormRequest;

class TimeUserScheduleRequest extends FormRequest
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
            'company_id' => 'required|integer|exists:companies,id',
            'departement' => 'required|integer|exists:departements,id',
            'user_id' => 'required|array|min:1',
            'user_id.*' => 'integer|exists:users,id',
            'time_work_id' => 'required|integer|exists:time_workes,id',
            'work_day_start' => 'required|date|before_or_equal:work_day_finish',
            'work_day_finish' => 'required|date|after_or_equal:work_day_start',
            'dayoff' => 'nullable|array',
            'dayoff.*' => 'in:Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
        ];
    }
}
