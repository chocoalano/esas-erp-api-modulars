<?php

/**
 * Created by Reliese Model.
 */

namespace App\GeneralModule\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class UserSalary
 *
 * @property int $id
 * @property int $user_id
 * @property float $basic_salary
 * @property string $payment_type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class UserSalary extends Model
{
	protected $table = 'user_salaries';

	protected $casts = [
		'user_id' => 'int',
		'basic_salary' => 'float'
	];

	protected $fillable = [
		'user_id',
		'basic_salary',
		'payment_type'
	];
}
