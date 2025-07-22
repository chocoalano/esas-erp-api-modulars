<?php

/**
 * Created by Reliese Model.
 */

namespace App\HrisModule\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class QrPresenceTransaction
 *
 * @property int $id
 * @property int $qr_presence_id
 * @property int $user_attendance_id
 * @property string $token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class QrPresenceTransaction extends Model
{
	protected $table = 'qr_presence_transactions';

	protected $casts = [
		'qr_presence_id' => 'int',
		'user_attendance_id' => 'int'
	];

	protected $hidden = [
		'token'
	];

	protected $fillable = [
		'qr_presence_id',
		'user_attendance_id',
		'token'
	];
}
