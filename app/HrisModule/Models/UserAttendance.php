<?php

/**
 * Created by Reliese Model.
 */

namespace App\HrisModule\Models;

use App\GeneralModule\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Class UserAttendance
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $user_timework_schedule_id
 * @property Carbon|null $time_in
 * @property Carbon|null $time_out
 * @property string|null $type_in
 * @property string|null $type_out
 * @property string|null $lat_in
 * @property string|null $lat_out
 * @property string|null $long_in
 * @property string|null $long_out
 * @property string|null $image_in
 * @property string|null $image_out
 * @property string $status_in
 * @property string $status_out
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class UserAttendance extends Model
{
    protected $table = 'user_attendances';

    protected $casts = [
        'user_id' => 'int',
        'user_timework_schedule_id' => 'int',
        'time_in' => 'datetime',
        'time_out' => 'datetime',
        'created_by' => 'int',
        'updated_by' => 'int'
    ];

    public function getTimeInAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('H:i:s') : null;
    }

    public function getTimeOutAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('H:i:s') : null;
    }


    protected $fillable = [
        'user_id',
        'user_timework_schedule_id',
        'time_in',
        'time_out',
        'type_in',
        'type_out',
        'lat_in',
        'lat_out',
        'long_in',
        'long_out',
        'image_in',
        'image_out',
        'status_in',
        'status_out',
        'date_presence',
        'created_by',
        'updated_by'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public function getUserDeptAttribute()
    {
        return DB::table('users as u')
            ->join('user_employes as ue', 'u.id', '=', 'ue.user_id')
            ->join('departements as d', 'ue.departement_id', '=', 'd.id')
            ->where('u.id', $this->user_id)
            ->pluck('d.name')
            ->first() ?? 'Unknown';
    }
    public function schedule()
    {
        return $this->belongsTo(UserTimeworkSchedule::class, 'user_timework_schedule_id', 'id');
    }
    public function qrPresenceTransactions()
    {
        return $this->hasOne(QrPresenceTransaction::class, 'user_attendance_id');
    }
}
