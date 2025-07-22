<?php

/**
 * Created by Reliese Model.
 */

namespace App\GeneralModule\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class UserDetail
 *
 * @property int $id
 * @property int $user_id
 * @property string $phone
 * @property string $placebirth
 * @property Carbon $datebirth
 * @property string $gender
 * @property string|null $blood
 * @property string|null $marital_status
 * @property string|null $religion
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class UserDetail extends Model
{
	protected $table = 'user_details';

	protected $casts = [
		'user_id' => 'int',
		'datebirth' => 'datetime'
	];

	protected $fillable = [
		'user_id',
		'phone',
		'placebirth',
		'datebirth',
		'gender',
		'blood',
		'marital_status',
		'religion'
	];
}
