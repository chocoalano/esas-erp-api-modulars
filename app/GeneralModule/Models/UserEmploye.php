<?php

/**
 * Created by Reliese Model.
 */

namespace App\GeneralModule\Models;

use App\HrisModule\Models\Departement;
use App\HrisModule\Models\JobLevel;
use App\HrisModule\Models\JobPosition;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class UserEmploye
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $departement_id
 * @property int|null $job_position_id
 * @property int|null $job_level_id
 * @property int|null $approval_line_id
 * @property int|null $approval_manager_id
 * @property Carbon $join_date
 * @property Carbon $sign_date
 * @property Carbon|null $resign_date
 * @property string|null $bank_name
 * @property string|null $bank_number
 * @property string|null $bank_holder
 * @property int|null $saldo_cuti
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class UserEmploye extends Model
{
	protected $table = 'user_employes';

	protected $casts = [
		'user_id' => 'int',
		'departement_id' => 'int',
		'job_position_id' => 'int',
		'job_level_id' => 'int',
		'approval_line_id' => 'int',
		'approval_manager_id' => 'int',
		'join_date' => 'datetime',
		'sign_date' => 'datetime',
		'resign_date' => 'datetime',
		'saldo_cuti' => 'int'
	];

	protected $fillable = [
		'user_id',
		'departement_id',
		'job_position_id',
		'job_level_id',
		'approval_line_id',
		'approval_manager_id',
		'join_date',
		'sign_date',
		'resign_date',
		'bank_name',
		'bank_number',
		'bank_holder',
		'saldo_cuti'
	];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function approval_line()
    {
        return $this->belongsTo(User::class);
    }
    public function approval_manager()
    {
        return $this->belongsTo(User::class);
    }
    // Dalam model User.php
    public static function approval_hr()
    {
        return self::whereHas('employee', function ($query) {
            $query->whereHas('departement', function ($dept) {
                $dept->where('name', 'HRGA');
            })
                ->whereHas('jobLevel', function ($lvl) {
                    $lvl->where('name', 'MANAGER');
                });
        })->first();
    }


    public function departement()
    {
        return $this->belongsTo(Departement::class);
    }

    public function jobPosition()
    {
        return $this->belongsTo(JobPosition::class);
    }

    public function jobLevel()
    {
        return $this->belongsTo(JobLevel::class);
    }
}
