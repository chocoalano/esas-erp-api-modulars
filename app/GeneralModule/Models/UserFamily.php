<?php

/**
 * Created by Reliese Model.
 */

namespace App\GeneralModule\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class UserFamily
 *
 * @property int $id
 * @property int $user_id
 * @property string $fullname
 * @property string $relationship
 * @property Carbon $birthdate
 * @property string|null $marital_status
 * @property string $job
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class UserFamily extends Model
{
	protected $table = 'user_families';

	protected $casts = [
		'user_id' => 'int',
		'birthdate' => 'datetime'
	];

	protected $fillable = [
		'user_id',
		'fullname',
		'relationship',
		'birthdate',
		'marital_status',
		'job'
	];
}
