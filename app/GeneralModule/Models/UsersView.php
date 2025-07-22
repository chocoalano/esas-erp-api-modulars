<?php

/**
 * Created by Reliese Model.
 */

namespace App\GeneralModule\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class UsersView
 *
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string $nip
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $avatar
 * @property string $status
 * @property string|null $remember_token
 * @property string|null $device_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $company
 * @property string $company_lat
 * @property string $company_long
 * @property string $company_radius
 * @property string $company_address
 * @property float $basic_salary
 * @property string $payment_type
 * @property string|null $bank_name
 * @property string|null $bank_number
 * @property string|null $bank_holder
 * @property string $phone
 * @property string $placebirth
 * @property Carbon $datebirth
 * @property string $gender
 * @property string|null $blood
 * @property string|null $marital_status
 * @property string|null $religion
 * @property string $identity_type
 * @property string $identity_numbers
 * @property string $province
 * @property string $city
 * @property string $citizen_address
 * @property string $residential_address
 * @property int|null $departement_id
 * @property int|null $job_position_id
 * @property int|null $job_level_id
 * @property int|null $approval_line_id
 * @property int|null $approval_manager_id
 * @property Carbon $join_date
 * @property Carbon $sign_date
 * @property Carbon|null $resign_date
 * @property string $departement
 * @property string $position
 * @property string $level
 *
 * @package App\Models
 */
class UsersView extends Model
{
	protected $table = 'users_view';
	public $incrementing = false;

	protected $casts = [
		'id' => 'int',
		'company_id' => 'int',
		'email_verified_at' => 'datetime',
		'basic_salary' => 'float',
		'datebirth' => 'datetime',
		'departement_id' => 'int',
		'job_position_id' => 'int',
		'job_level_id' => 'int',
		'approval_line_id' => 'int',
		'approval_manager_id' => 'int',
		'join_date' => 'datetime',
		'sign_date' => 'datetime',
		'resign_date' => 'datetime'
	];

	protected $hidden = [
		'password',
		'remember_token'
	];

	protected $fillable = [
		'id',
		'company_id',
		'name',
		'nip',
		'email',
		'email_verified_at',
		'password',
		'avatar',
		'status',
		'remember_token',
		'device_id',
		'company',
		'company_lat',
		'company_long',
		'company_radius',
		'company_address',
		'basic_salary',
		'payment_type',
		'bank_name',
		'bank_number',
		'bank_holder',
		'phone',
		'placebirth',
		'datebirth',
		'gender',
		'blood',
		'marital_status',
		'religion',
		'identity_type',
		'identity_numbers',
		'province',
		'city',
		'citizen_address',
		'residential_address',
		'departement_id',
		'job_position_id',
		'job_level_id',
		'approval_line_id',
		'approval_manager_id',
		'join_date',
		'sign_date',
		'resign_date',
		'departement',
		'position',
		'level'
	];
}
