<?php

/**
 * Created by Reliese Model.
 */

namespace App\HrisModule\Models;

use App\GeneralModule\Models\Company;
use App\GeneralModule\Models\UserEmploye;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Departement
 *
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @package App\Models
 */
class Departement extends Model
{
    use SoftDeletes;
    protected $table = 'departements';

    protected $casts = [
        'company_id' => 'int'
    ];

    protected $fillable = [
        'company_id',
        'name'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function timeWorks()
    {
        return $this->hasMany(TimeWorke::class, 'departemen_id', 'id');
    }

    public function jobPositions()
    {
        return $this->hasMany(JobPosition::class);
    }

    public function jobLevels()
    {
        return $this->hasMany(JobLevel::class);
    }

    public function employees()
    {
        return $this->hasMany(UserEmploye::class);
    }
}
