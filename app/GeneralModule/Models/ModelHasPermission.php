<?php

/**
 * Created by Reliese Model.
 */

namespace App\GeneralModule\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ModelHasPermission
 *
 * @property int $permission_id
 * @property string $model_type
 * @property int $model_id
 *
 * @package App\Models
 */
class ModelHasPermission extends Model
{
	protected $table = 'model_has_permissions';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'permission_id' => 'int',
		'model_id' => 'int'
	];

	protected $fillable = [
		'permission_id',
		'model_type',
		'model_id'
	];
}
