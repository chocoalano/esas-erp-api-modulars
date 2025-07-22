<?php

/**
 * Created by Reliese Model.
 */

namespace App\GeneralModule\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Import
 *
 * @property int $id
 * @property Carbon|null $completed_at
 * @property string $file_name
 * @property string $file_path
 * @property string $importer
 * @property int $processed_rows
 * @property int $total_rows
 * @property int $successful_rows
 * @property int $user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class Import extends Model
{
	protected $table = 'imports';

	protected $casts = [
		'completed_at' => 'datetime',
		'processed_rows' => 'int',
		'total_rows' => 'int',
		'successful_rows' => 'int',
		'user_id' => 'int'
	];

	protected $fillable = [
		'completed_at',
		'file_name',
		'file_path',
		'importer',
		'processed_rows',
		'total_rows',
		'successful_rows',
		'user_id'
	];
}
