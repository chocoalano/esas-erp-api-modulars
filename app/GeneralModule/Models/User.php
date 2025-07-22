<?php

/**
 * Created by Reliese Model.
 */

namespace App\GeneralModule\Models;

use App\GeneralModule\Models\ActivityLog;
use App\HrisModule\Models\LogUserAttendance;
use App\HrisModule\Models\UserAttendance;
use App\HrisModule\Models\UserTimeworkSchedule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes, HasApiTokens;
	protected $table = 'users';

	protected $casts = [
		'company_id' => 'int',
		'email_verified_at' => 'datetime'
	];

	protected $hidden = [
		'password',
		'remember_token'
	];

	protected $fillable = [
		'company_id',
		'name',
		'nip',
		'email',
		'email_verified_at',
		'password',
		'avatar',
		'status',
		'remember_token',
		'device_id'
	];

    public const STATUS = [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'resign' => 'Resign',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function timeworkSchedules()
    {
        return $this->hasMany(UserTimeworkSchedule::class);
    }

    public function attendances()
    {
        return $this->hasMany(UserAttendance::class);
    }

    public function details()
    {
        return $this->hasOne(UserDetail::class);
    }
    public function address()
    {
        return $this->hasOne(UserAddress::class);
    }

    public function salaries()
    {
        return $this->hasOne(UserSalary::class);
    }

    public function families()
    {
        return $this->hasMany(UserFamily::class);
    }

    public function formalEducations()
    {
        return $this->hasMany(UserFormalEducation::class);
    }

    public function informalEducations()
    {
        return $this->hasMany(UserInformalEducation::class);
    }

    public function workExperiences()
    {
        return $this->hasMany(UserWorkExperience::class);
    }

    public function employee()
    {
        return $this->hasOne(UserEmploye::class);
    }

	public function activity_logs()
	{
		return $this->hasMany(ActivityLog::class);
	}

	public function log_user_attendances()
	{
		return $this->hasMany(LogUserAttendance::class);
	}
	public function fcm_token()
	{
		return $this->hasOne(FcmModel::class);
	}
}
