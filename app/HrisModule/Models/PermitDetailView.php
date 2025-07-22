<?php

/**
 * Created by Reliese Model.
 */

namespace App\HrisModule\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class PermitDetailView
 *
 * @property int $id
 * @property string $permit_numbers
 * @property int $user_id
 * @property int $permit_type_id
 * @property int $user_timework_schedule_id
 * @property Carbon|null $timein_adjust
 * @property Carbon|null $timeout_adjust
 * @property int|null $current_shift_id
 * @property int|null $adjust_shift_id
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property Carbon|null $start_time
 * @property Carbon|null $end_time
 * @property string|null $notes
 * @property string|null $file
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $type
 * @property bool $is_payed
 * @property bool $approve_line
 * @property bool $approve_manager
 * @property bool $approve_hr
 * @property bool $with_file
 * @property string $company
 * @property string $user_name
 * @property string $nip
 * @property string|null $departement
 * @property string|null $position
 * @property string|null $levels
 *
 * @package App\Models
 */
class PermitDetailView extends Model
{
	protected $table = 'permit_detail_view';
	public $incrementing = false;

	protected $casts = [
		'id' => 'int',
		'user_id' => 'int',
		'permit_type_id' => 'int',
		'user_timework_schedule_id' => 'int',
		'timein_adjust' => 'datetime',
		'timeout_adjust' => 'datetime',
		'current_shift_id' => 'int',
		'adjust_shift_id' => 'int',
		'start_date' => 'datetime',
		'end_date' => 'datetime',
		'start_time' => 'datetime',
		'end_time' => 'datetime',
		'is_payed' => 'bool',
		'approve_line' => 'bool',
		'approve_manager' => 'bool',
		'approve_hr' => 'bool',
		'with_file' => 'bool'
	];

	protected $fillable = [
		'id',
		'permit_numbers',
		'user_id',
		'permit_type_id',
		'user_timework_schedule_id',
		'timein_adjust',
		'timeout_adjust',
		'current_shift_id',
		'adjust_shift_id',
		'start_date',
		'end_date',
		'start_time',
		'end_time',
		'notes',
		'file',
		'type',
		'is_payed',
		'approve_line',
		'approve_manager',
		'approve_hr',
		'with_file',
		'company',
		'user_name',
		'nip',
		'departement',
		'position',
		'levels'
	];
}
