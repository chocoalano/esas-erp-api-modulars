<?php

/**
 * Created by Reliese Model.
 */

namespace App\HrisModule\Models;

use App\GeneralModule\Models\Company;
use App\HrisModule\Models\Departement; // Import Departement model
use App\HrisModule\Models\UserTimeworkSchedule; // Import UserTimeworkSchedule model
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TimeWorke
 *
 * @property int $id
 * @property int $company_id
 * @property int $departemen_id
 * @property string $name
 * @property Carbon $in
 * @property Carbon $out
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Company $company
 * @property Departement $departement
 * @property \Illuminate\Database\Eloquent\Collection|UserTimeworkSchedule[] $userSchedules
 *
 * @package App\Models
 */
class TimeWorke extends Model
{
    protected $table = 'time_workes';

    protected $casts = [
        'company_id' => 'int',
        'departemen_id' => 'int',
        'in' => 'datetime',
        'out' => 'datetime'
    ];

    protected $fillable = [
        'company_id',
        'departemen_id',
        'name',
        'in',
        'out'
    ];

    // --- Accessor untuk kolom 'in' ---
    /**
     * Get the 'in' time formatted as HH:mm.
     *
     * @param  string|null  $value
     * @return string|null
     */
    public function getInAttribute($value)
    {
        // Pastikan nilai bukan null sebelum memformat
        return $value ? Carbon::parse($value)->format('H:i') : null;
    }

    // --- Accessor untuk kolom 'out' ---
    /**
     * Get the 'out' time formatted as HH:mm.
     *
     * @param  string|null  $value
     * @return string|null
     */
    public function getOutAttribute($value)
    {
        // Pastikan nilai bukan null sebelum memformat
        return $value ? Carbon::parse($value)->format('H:i') : null;
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function departement()
    {
        // Pastikan kelas Departement diimpor
        return $this->belongsTo(Departement::class, 'departemen_id', 'id');
    }

    public function userSchedules()
    {
        // Pastikan kelas UserTimeworkSchedule diimpor
        return $this->hasMany(UserTimeworkSchedule::class);
    }
}
