<?php

/**
 * Created by Reliese Model.
 */

namespace App\GeneralModule\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class FailedImportRow
 *
 * @property int $id
 * @property string $data
 * @property int $import_id
 * @property string|null $validation_error
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class FailedImportRow extends Model
{
	protected $table = 'failed_import_rows';

	protected $casts = [
		'import_id' => 'int'
	];

	protected $fillable = [
		'data',
		'import_id',
		'validation_error'
	];
}
