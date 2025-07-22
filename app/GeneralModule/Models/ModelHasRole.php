<?php

/**
 * Created by Reliese Model.
 */

namespace App\GeneralModule\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ModelHasRole
 *
 * @property int $role_id
 * @property string $model_type
 * @property int $model_id
 *
 * @package App\Models
 */
class ModelHasRole extends Model
{
	protected $table = 'model_has_roles';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'role_id' => 'int',
		'model_id' => 'int'
	];

	protected $fillable = [
		'role_id',
		'model_type',
		'model_id'
	];
}
