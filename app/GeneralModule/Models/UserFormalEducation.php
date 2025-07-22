<?php

/**
 * Created by Reliese Model.
 */

namespace App\GeneralModule\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class UserFormalEducation
 *
 * @property int $id
 * @property int $user_id
 * @property string $institution
 * @property string $majors
 * @property float $score
 * @property Carbon|null $start
 * @property Carbon|null $finish
 * @property string $status
 * @property bool $certification
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class UserFormalEducation extends Model
{
	protected $table = 'user_formal_educations';

	protected $casts = [
		'user_id' => 'int',
		'score' => 'float',
		'start' => 'datetime',
		'finish' => 'datetime',
		'certification' => 'bool'
	];

	protected $fillable = [
		'user_id',
		'institution',
		'majors',
		'score',
		'start',
		'finish',
		'status',
		'certification'
	];
}
