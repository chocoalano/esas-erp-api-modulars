<?php

/**
 * Created by Reliese Model.
 */

namespace App\GeneralModule\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class UserInformalEducation
 *
 * @property int $id
 * @property int $user_id
 * @property string $institution
 * @property Carbon|null $start
 * @property Carbon|null $finish
 * @property string $type
 * @property int $duration
 * @property string $status
 * @property bool $certification
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class UserInformalEducation extends Model
{
	protected $table = 'user_informal_educations';

	protected $casts = [
		'user_id' => 'int',
		'start' => 'datetime',
		'finish' => 'datetime',
		'duration' => 'int',
		'certification' => 'bool'
	];

	protected $fillable = [
		'user_id',
		'institution',
		'start',
		'finish',
		'type',
		'duration',
		'status',
		'certification'
	];
}
