<?php

/**
 * Created by Reliese Model.
 */

namespace App\GeneralModule\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Documentation
 *
 * @property int $id
 * @property string $title
 * @property string|null $subtitle
 * @property string $text_docs
 * @property bool $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class Documentation extends Model
{
	protected $table = 'documentations';

	protected $casts = [
		'status' => 'bool'
	];

	protected $fillable = [
		'title',
		'subtitle',
		'text_docs',
		'status'
	];
}
