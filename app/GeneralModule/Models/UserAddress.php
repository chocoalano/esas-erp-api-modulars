<?php

/**
 * Created by Reliese Model.
 */

namespace App\GeneralModule\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class UserAddress
 *
 * @property int $id
 * @property int $user_id
 * @property string $identity_type
 * @property string $identity_numbers
 * @property string $province
 * @property string $city
 * @property string $citizen_address
 * @property string $residential_address
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class UserAddress extends Model
{
	protected $table = 'user_address';

	protected $casts = [
		'user_id' => 'int'
	];

	protected $fillable = [
		'user_id',
		'identity_type',
		'identity_numbers',
		'province',
		'city',
		'citizen_address',
		'residential_address'
	];
}
