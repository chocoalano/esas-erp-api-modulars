<?php

/**
 * Created by Reliese Model.
 */

namespace App\HrisModule\Models;

use App\GeneralModule\Models\Company;
use App\GeneralModule\Models\User;
use App\GeneralModule\Models\UserEmploye;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class UserTimeworkSchedule
 *
 * @property int $id
 * @property int $user_id
 * @property int $time_work_id
 * @property Carbon $work_day
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class UserTimeworkSchedule extends Model
{
	protected $table = 'user_timework_schedules';

	protected $casts = [
		'user_id' => 'int',
		'time_work_id' => 'int',
		'work_day' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
	];

	protected $fillable = [
		'user_id',
		'time_work_id',
		'work_day'
	];

    // ACCESSORS

    public function getWorkDayAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d');
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d F y H:i:s');
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d F y H:i:s');
    }

    // RELATIONSHIPS
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function timework()
    {
        return $this->belongsTo(TimeWorke::class, 'time_work_id', 'id');
    }

    public function employee()
    {
        return $this->hasOneThrough(
            UserEmploye::class,    // Model yang ingin diakses
            User::class,    // Model perantara
            'id',   // Foreign key di tabel `users` (tabel perantara)
            'user_id',      // Foreign key di tabel `posts`
            'user_id',           // Primary key di tabel `countries`
            'id'            // Primary key di tabel `users`
        );
    }
    public function company()
    {
        return $this->hasOneThrough(
            Company::class,    // Model yang ingin diakses
            User::class,    // Model perantara
            'company_id',   // Foreign key di tabel `users` (tabel perantara)
            'id',      // Foreign key di tabel `posts`
            'user_id',           // Primary key di tabel `countries`
            'id'            // Primary key di tabel `users`
        );
    }
}
