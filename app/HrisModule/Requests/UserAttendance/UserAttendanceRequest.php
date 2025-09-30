<?php

namespace App\HrisModule\Requests\UserAttendance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use DateTimeImmutable;

/**
 * @method mixed input(string $key = null, $default = null)
 * @method array all($keys = null)
 * @method void merge(array $input)
 * @method void replace(array $input)
 */

class UserAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Terima HH:MM atau HH:MM:SS (00-23:00-59:00-59)
     */
    private const TIME_REGEX = '/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/';

    public function rules(): array
    {
        /** @var int|null $id */
        $id = request()->route('id'); // jika diperlukan untuk update rules

        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],

            // pastikan benar ke tabel jadwal
            'user_timework_schedule_id' => ['required', 'integer', 'exists:user_timework_schedules,id'],

            // waktu (format HH:MM atau HH:MM:SS)
            'time_in'  => ['required', 'regex:' . self::TIME_REGEX],
            'time_out' => ['required', 'regex:' . self::TIME_REGEX],

            // tipe & status (batasi nilai agar konsisten)
            'type_in'  => ['required', 'string', 'max:255', Rule::in(['qrcode', 'face-device', 'face-geolocation'])],
            'type_out' => ['required', 'string', 'max:255', Rule::in(['qrcode', 'face-device', 'face-geolocation'])],

            // koordinat: angka + batas lat/long valid + presisi desimal
            'lat_in'   => ['required', 'numeric', 'between:-90,90',  'decimal:0,8'],
            'lat_out'  => ['required', 'numeric', 'between:-90,90',  'decimal:0,8'],
            'long_in'  => ['required', 'numeric', 'between:-180,180','decimal:0,8'],
            'long_out' => ['required', 'numeric', 'between:-180,180','decimal:0,8'],

            // file gambar opsional
            'image_in'  => ['nullable', 'image', 'mimes:jpeg,png,jpg,svg,webp', 'max:5048'],
            'image_out' => ['nullable', 'image', 'mimes:jpeg,png,jpg,svg,webp', 'max:5048'],

            // status — silakan sesuaikan enum berikut dengan kebutuhan bisnis
            'status_in'  => ['required', 'string', 'max:255', Rule::in(['present', 'late', 'absent', 'excused'])],
            'status_out' => ['required', 'string', 'max:255', Rule::in(['present', 'early', 'overtime', 'excused'])],

            // metadata
            'created_by' => ['nullable', 'integer', 'exists:users,id'],
            'updated_by' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * Normalisasi ringan sebelum validasi (trim string).
     * Tanpa $this->string() agar bebas warning Intelephense.
     */
    protected function prepareForValidation(): void
{
    $input = $this->all();

    $input['type_in']    = isset($input['type_in'])    ? trim((string) $input['type_in'])    : null;
    $input['type_out']   = isset($input['type_out'])   ? trim((string) $input['type_out'])   : null;
    $input['status_in']  = isset($input['status_in'])  ? trim((string) $input['status_in'])  : null;
    $input['status_out'] = isset($input['status_out']) ? trim((string) $input['status_out']) : null;

    $this->replace($input);
}


    /**
     * Validasi lintas-field setelah rules dasar lulus.
     * Contoh: time_out tidak boleh lebih awal dari time_in.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $timeIn  = $this->input('time_in');
            $timeOut = $this->input('time_out');

            if (is_string($timeIn) && is_string($timeOut)
                && preg_match(self::TIME_REGEX, $timeIn)
                && preg_match(self::TIME_REGEX, $timeOut)
            ) {
                // Normalisasi ke format HH:MM:SS untuk pembandingan yang konsisten
                $norm = function (string $t): string {
                    return strlen($t) === 5 ? $t . ':00' : $t; // HH:MM -> HH:MM:SS
                };

                $tIn  = DateTimeImmutable::createFromFormat('H:i:s', $norm($timeIn));
                $tOut = DateTimeImmutable::createFromFormat('H:i:s', $norm($timeOut));

                if ($tIn && $tOut && $tOut < $tIn) {
                    $v->errors()->add('time_out', 'Jam pulang tidak boleh lebih awal dari jam masuk.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'user_id.exists' => 'User yang dipilih tidak ditemukan.',
            'user_timework_schedule_id.exists' => 'Jadwal kerja yang dipilih tidak ditemukan.',

            'time_in.regex'  => 'Format jam masuk harus HH:MM atau HH:MM:SS.',
            'time_out.regex' => 'Format jam pulang harus HH:MM atau HH:MM:SS.',

            'type_in.in'  => 'Tipe check-in harus salah satu dari: in/out.',
            'type_out.in' => 'Tipe check-out harus salah satu dari: in/out.',

            'lat_in.between'   => 'Latitude check-in harus di antara -90 sampai 90.',
            'lat_out.between'  => 'Latitude check-out harus di antara -90 sampai 90.',
            'long_in.between'  => 'Longitude check-in harus di antara -180 sampai 180.',
            'long_out.between' => 'Longitude check-out harus di antara -180 sampai 180.',

            'image_in.image'  => 'File check-in harus berupa gambar.',
            'image_out.image' => 'File check-out harus berupa gambar.',
        ];
    }

    public function attributes(): array
    {
        return [
            'user_id' => 'pengguna',
            'user_timework_schedule_id' => 'jadwal kerja',
            'time_in'  => 'jam masuk',
            'time_out' => 'jam pulang',
            'type_in'  => 'tipe masuk',
            'type_out' => 'tipe pulang',
            'lat_in'   => 'latitude masuk',
            'lat_out'  => 'latitude pulang',
            'long_in'  => 'longitude masuk',
            'long_out' => 'longitude pulang',
            'image_in'  => 'foto masuk',
            'image_out' => 'foto pulang',
            'status_in'  => 'status masuk',
            'status_out' => 'status pulang',
        ];
    }
}
