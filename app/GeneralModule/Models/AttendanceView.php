<?php

/**
 * Created by Reliese Model.
 */

namespace App\GeneralModule\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class AttendanceView
 *
 * @property int $id
 * @property int $user_id
 * @property int $company_id
 * @property string $name
 * @property string $nip
 * @property string|null $avatar
 * @property int|null $departement_id
 * @property int|null $job_position_id
 * @property int|null $job_level_id
 * @property int|null $approval_line_id
 * @property int|null $approval_manager_id
 * @property Carbon $join_date
 * @property Carbon $sign_date
 * @property string $departement
 * @property string $position
 * @property string $level
 * @property Carbon|null $work_day
 * @property string|null $shiftname
 * @property Carbon|null $in
 * @property Carbon|null $out
 * @property int|null $user_timework_schedule_id
 * @property Carbon|null $time_in
 * @property string|null $lat_in
 * @property string|null $long_in
 * @property string|null $image_in
 * @property string $status_in
 * @property Carbon|null $time_out
 * @property string|null $lat_out
 * @property string|null $long_out
 * @property string|null $image_out
 * @property string $status_out
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class AttendanceView extends Model
{
	protected $table = 'attendance_view';
	public $incrementing = false;

	protected $casts = [
		'id' => 'int',
		'user_id' => 'int',
		'company_id' => 'int',
		'departement_id' => 'int',
		'job_position_id' => 'int',
		'job_level_id' => 'int',
		'approval_line_id' => 'int',
		'approval_manager_id' => 'int',
		'join_date' => 'datetime',
		'sign_date' => 'datetime',
		'work_day' => 'datetime',
		'in' => 'datetime',
		'out' => 'datetime',
		'user_timework_schedule_id' => 'int',
		'time_in' => 'datetime',
		'time_out' => 'datetime'
	];

	protected $fillable = [
		'id',
		'user_id',
		'company_id',
		'name',
		'nip',
		'avatar',
		'departement_id',
		'job_position_id',
		'job_level_id',
		'approval_line_id',
		'approval_manager_id',
		'join_date',
		'sign_date',
		'departement',
		'position',
		'level',
		'work_day',
		'shiftname',
		'in',
		'out',
		'user_timework_schedule_id',
		'time_in',
		'lat_in',
		'long_in',
		'image_in',
		'status_in',
		'time_out',
		'lat_out',
		'long_out',
		'image_out',
		'status_out'
	];
}
