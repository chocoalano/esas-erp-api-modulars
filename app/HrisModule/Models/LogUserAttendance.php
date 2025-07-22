<?php

/**
 * Created by Reliese Model.
 */

namespace App\HrisModule\Models;

use App\GeneralModule\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class LogUserAttendance
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property \App\GeneralModule\Models\User|null $user
 *
 * @package App\Models
 */
class LogUserAttendance extends Model
{
	protected $table = 'log_user_attendances';

	protected $casts = [
		'user_id' => 'int'
	];

	protected $fillable = [
		'user_id',
		'type'
	];

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
