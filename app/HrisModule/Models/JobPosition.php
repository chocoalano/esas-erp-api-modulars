<?php

/**
 * Created by Reliese Model.
 */

namespace App\HrisModule\Models;

use App\GeneralModule\Models\Company;
use App\GeneralModule\Models\UserEmploye;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class JobPosition
 *
 * @property int $id
 * @property int $company_id
 * @property int $departement_id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @package App\Models
 */
class JobPosition extends Model
{
	use SoftDeletes;
	protected $table = 'job_positions';

	protected $casts = [
		'company_id' => 'int',
		'departement_id' => 'int'
	];

	protected $fillable = [
		'company_id',
		'departement_id',
		'name'
	];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function departement()
    {
        return $this->belongsTo(Departement::class);
    }
    public function employees()
    {
        return $this->hasMany(UserEmploye::class);
    }
}
