<?php

/**
 * Created by Reliese Model.
 */

namespace App\GeneralModule\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ActivityLog
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $method
 * @property string $url
 * @property string|null $action
 * @property string|null $model_type
 * @property int|null $model_id
 * @property string|null $payload
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @property User|null $user
 *
 * @package App\Models
 */
class ActivityLog extends Model
{
	use SoftDeletes;
	protected $table = 'activity_logs';

	protected $casts = [
		'user_id' => 'int',
		'model_id' => 'int',
        'payload' => 'json'
	];

	protected $fillable = [
		'user_id',
		'method',
		'url',
		'action',
		'model_type',
		'model_id',
		'payload',
		'ip_address',
		'user_agent'
	];

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
