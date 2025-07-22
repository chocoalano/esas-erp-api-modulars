<?php

/**
 * Created by Reliese Model.
 */

namespace App\GeneralModule\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class RoleHasPermission
 *
 * @property int $permission_id
 * @property int $role_id
 *
 * @package App\Models
 */
class RoleHasPermission extends Model
{
	protected $table = 'role_has_permissions';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'permission_id' => 'int',
		'role_id' => 'int'
	];

	protected $fillable = [
		'permission_id',
		'role_id'
	];
}
