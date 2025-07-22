<?php

/**
 * Created by Reliese Model.
 */

namespace App\HrisModule\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class PermitType
 *
 * @property int $id
 * @property string $type
 * @property bool $is_payed
 * @property bool $approve_line
 * @property bool $approve_manager
 * @property bool $approve_hr
 * @property bool $with_file
 * @property bool $show_mobile
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @package App\Models
 */
class PermitType extends Model
{
	use SoftDeletes;
	protected $table = 'permit_types';

	protected $casts = [
		'is_payed' => 'bool',
		'approve_line' => 'bool',
		'approve_manager' => 'bool',
		'approve_hr' => 'bool',
		'with_file' => 'bool',
		'show_mobile' => 'bool'
	];

	protected $fillable = [
		'type',
		'is_payed',
		'approve_line',
		'approve_manager',
		'approve_hr',
		'with_file',
		'show_mobile'
	];
}
