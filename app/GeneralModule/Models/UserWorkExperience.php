<?php

/**
 * Created by Reliese Model.
 */

namespace App\GeneralModule\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class UserWorkExperience
 *
 * @property int $id
 * @property int $user_id
 * @property string $company_name
 * @property Carbon|null $start
 * @property Carbon|null $finish
 * @property string|null $position
 * @property bool $certification
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class UserWorkExperience extends Model
{
	protected $table = 'user_work_experiences';

	protected $casts = [
		'user_id' => 'int',
		'start' => 'datetime',
		'finish' => 'datetime',
		'certification' => 'bool'
	];

	protected $fillable = [
		'user_id',
		'company_name',
		'start',
		'finish',
		'position',
		'certification'
	];
}
