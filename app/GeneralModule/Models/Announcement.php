<?php

/**
 * Created by Reliese Model.
 */

namespace App\GeneralModule\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Announcement
 *
 * @property int $id
 * @property int $company_id
 * @property int $user_id
 * @property string $title
 * @property bool $status
 * @property string $content
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class Announcement extends Model
{
	protected $table = 'announcements';

	protected $casts = [
		'company_id' => 'int',
		'user_id' => 'int',
		'status' => 'bool'
	];

	protected $fillable = [
		'company_id',
		'user_id',
		'title',
		'status',
		'content'
	];
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
}
