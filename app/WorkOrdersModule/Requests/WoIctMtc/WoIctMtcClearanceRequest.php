<?php

namespace App\WorkOrdersModule\Requests\WoIctMtc;

use Illuminate\Foundation\Http\FormRequest;

class WoIctMtcClearanceRequest extends FormRequest
{
    // Opsional: hentikan pada error pertama agar feedback lebih fokus
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalisasi lebih dulu supaya "ya/tidak", "on/off", "1/0" dibaca sebagai boolean.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'hygiene_clearance'      => $this->toBool($this->input('hygiene_clearance')),
            'maintenance_clearance'  => $this->toBool($this->input('maintenance_clearance')),
        ]);
    }

    public function rules(): array
    {
        return [
            'hygiene_clearance' => ['bail', 'required', 'boolean'],
            'maintenance_clearance' => ['bail', 'required', 'boolean'],
        ];
    }

    /**
     * (OPSIONAL) Jika ingin memaksa minimal salah satu bernilai true, aktifkan blok ini.
     */
    // public function withValidator($validator)
    // {
    //     $validator->after(function ($v) {
    //         $h = (bool) $this->input('hygiene_clearance');
    //         $m = (bool) $this->input('maintenance_clearance');
    //         if (! $h && ! $m) {
    //             // Tambahkan error ke salah satu field agar jelas
    //             $v->errors()->add('hygiene_clearance', 'Minimal salah satu clearance harus bernilai Ya/True.');
    //         }
    //     });
    // }

    public function messages(): array
    {
        return [
            'hygiene_clearance.required'     => 'Hygiene Clearance wajib diisi.',
            'hygiene_clearance.boolean'      => 'Hygiene Clearance harus bernilai Ya/Tidak.',
            'maintenance_clearance.required' => 'Maintenance Clearance wajib diisi.',
            'maintenance_clearance.boolean'  => 'Maintenance Clearance harus bernilai Ya/Tidak.',
        ];
    }

    public function attributes(): array
    {
        return [
            'hygiene_clearance'     => 'Hygiene Clearance',
            'maintenance_clearance' => 'Maintenance Clearance',
        ];
    }

    /**
     * Helper: robust boolean caster untuk berbagai input umum.
     */
    private function toBool($value): mixed
    {
        if (is_bool($value)) return $value;

        // Angka/string angka
        if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))) {
            return (int) $value === 1 ? true : ((int) $value === 0 ? false : $value);
        }

        if (is_string($value)) {
            $v = trim(mb_strtolower($value));
            $truthy = ['ya','y','yes','true','on'];
            $falsy  = ['tidak','tdk','t','no','false','off','-'];
            if (in_array($v, $truthy, true)) return true;
            if (in_array($v, $falsy, true))  return false;
        }

        // Biarkan nilai asli jika tidak terdeteksi; biar rule 'boolean' yang menolak
        return $value;
    }
}
