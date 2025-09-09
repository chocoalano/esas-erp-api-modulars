<?php

namespace App\HrisModule\Requests\TimeUserSchedule;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;

class TimeUserScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalisasi input ISO8601 -> tanggal lokal Asia/Jakarta (Y-m-d),
     * normalisasi boolean dan dayoff (Title Case).
     */
    protected function prepareForValidation(): void
    {
        $toLocalDate = function (?string $val): ?string {
            if ($val === null || $val === '') return null;
            try {
                // Parse apapun (ISO8601/Z/offset), konversi ke Asia/Jakarta, ambil tanggal saja
                return Carbon::parse($val)->setTimezone('Asia/Jakarta')->toDateString(); // Y-m-d
            } catch (InvalidFormatException $e) {
                return null; // biar rules menangkap sebagai invalid
            } catch (\Throwable $e) {
                return null;
            }
        };

        // Normalisasi dayoff: jadikan Title Case supaya lulus rule "in:Sunday,..."
        $dayoff = $this->input('dayoff');
        if (is_array($dayoff)) {
            $dayoff = array_map(function ($v) {
                if (!is_string($v)) return $v;
                $v = strtolower(trim($v));
                return ucfirst($v); // sunday -> Sunday
            }, $dayoff);
        }

        // Normalisasi boolean (jaga kalau datang sebagai "true"/"false")
        $isRolling = filter_var($this->input('is_rolling'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        $this->merge([
            'work_day_start'  => $toLocalDate($this->input('work_day_start')),
            'work_day_finish' => $toLocalDate($this->input('work_day_finish')),
            'dayoff'          => $dayoff,
            'is_rolling'      => $isRolling ?? false,
        ]);
    }

    public function rules(): array
    {
        $companyId = $this->input('company_id');
        $deptId    = $this->input('departement'); // field request-mu

        return [
            'company_id' => [
                'bail','required','integer',
                Rule::exists('companies','id'),
            ],

            'departement' => [
                'bail','required','integer',
                Rule::exists('departements','id')
                    ->where(fn($q) => $companyId !== null ? $q->where('company_id', $companyId) : $q),
            ],

            'user_id'   => ['bail','required','array','min:1'],
            'user_id.*' => [
                'bail','integer','distinct',
                Rule::exists('users','id')
                    ->where(fn($q) => $companyId !== null ? $q->where('company_id', $companyId) : $q),
            ],

            'time_work_id' => [
                'bail','required','integer',
                Rule::exists('time_workes','id')
                    ->where(function ($q) use ($companyId, $deptId) {
                        if ($companyId !== null) $q->where('company_id', $companyId);
                        if ($deptId !== null)    $q->where('departemen_id', $deptId);
                    }),
            ],

            'time_work_rolling_id' => [
                Rule::requiredIf(fn () => (bool) $this->boolean('is_rolling')),
                'nullable','integer',
                Rule::exists('time_workes','id')
                    ->where(function ($q) use ($companyId, $deptId) {
                        if ($companyId !== null) $q->where('company_id', $companyId);
                        if ($deptId !== null)    $q->where('departemen_id', $deptId);
                    }),
                'different:time_work_id',
            ],

            'is_rolling' => ['bail','required','boolean'],

            // Setelah dinormalisasi -> Y-m-d
            'work_day_start'  => ['bail','required','date_format:Y-m-d','before_or_equal:work_day_finish'],
            'work_day_finish' => ['bail','required','date_format:Y-m-d','after_or_equal:work_day_start'],

            'dayoff'   => ['nullable','array'],
            'dayoff.*' => ['in:Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'Perusahaan wajib diisi.',
            'company_id.exists'   => 'Perusahaan tidak valid.',

            'departement.required' => 'Departemen wajib diisi.',
            'departement.exists'   => 'Departemen tidak ditemukan pada perusahaan ini.',

            'user_id.required'   => 'Minimal pilih satu karyawan.',
            'user_id.array'      => 'Format user_id harus berupa array.',
            'user_id.min'        => 'Minimal pilih satu karyawan.',
            'user_id.*.integer'  => 'Setiap user_id harus berupa angka.',
            'user_id.*.distinct' => 'Terdapat duplikasi user pada daftar.',
            'user_id.*.exists'   => 'Salah satu user tidak terdaftar pada perusahaan ini.',

            'time_work_id.required' => 'Shift utama wajib diisi.',
            'time_work_id.exists'   => 'Shift utama tidak sesuai dengan perusahaan/departemen.',

            'time_work_rolling_id.required' => 'Shift rolling wajib diisi saat is_rolling = true.',
            'time_work_rolling_id.integer'  => 'Shift rolling harus berupa angka.',
            'time_work_rolling_id.exists'   => 'Shift rolling tidak sesuai dengan perusahaan/departemen.',
            'time_work_rolling_id.different'=> 'Shift rolling harus berbeda dari shift utama.',

            'is_rolling.required' => 'Status rolling wajib diisi.',
            'is_rolling.boolean'  => 'Status rolling harus bernilai boolean (true/false).',

            'work_day_start.required'        => 'Tanggal mulai kerja wajib diisi.',
            'work_day_start.date_format'     => 'Tanggal mulai harus berformat Y-m-d.',
            'work_day_start.before_or_equal' => 'Tanggal mulai tidak boleh setelah tanggal selesai.',

            'work_day_finish.required'       => 'Tanggal selesai kerja wajib diisi.',
            'work_day_finish.date_format'    => 'Tanggal selesai harus berformat Y-m-d.',
            'work_day_finish.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',

            'dayoff.array' => 'Format hari libur harus berupa array.',
            'dayoff.*.in'  => 'Hari libur hanya boleh: Sunday, Monday, Tuesday, Wednesday, Thursday, Friday, Saturday.',
        ];
    }

    public function attributes(): array
    {
        return [
            'company_id'            => 'perusahaan',
            'departement'           => 'departemen',
            'user_id'               => 'daftar karyawan',
            'user_id.*'             => 'karyawan',
            'time_work_id'          => 'shift utama',
            'time_work_rolling_id'  => 'shift rolling',
            'work_day_start'        => 'tanggal mulai',
            'work_day_finish'       => 'tanggal selesai',
            'dayoff'                => 'hari libur',
            'dayoff.*'              => 'hari libur',
        ];
    }
}
