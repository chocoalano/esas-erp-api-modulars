<?php

/**
 * Created by Reliese Model.
 */

namespace App\GeneralModule\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Setting
 *
 * @property int $id
 * @property int $company_id
 * @property bool $attendance_image_geolocation
 * @property bool $attendance_qrcode
 * @property bool $attendance_fingerprint
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @package App\Models
 */
class Setting extends Model
{
	use SoftDeletes;
	protected $table = 'settings';

	protected $casts = [
		'company_id' => 'int',
		'attendance_image_geolocation' => 'bool',
		'attendance_qrcode' => 'bool',
		'attendance_fingerprint' => 'bool'
	];

	protected $fillable = [
		'company_id',
		'attendance_image_geolocation',
		'attendance_qrcode',
		'attendance_fingerprint'
	];
}
