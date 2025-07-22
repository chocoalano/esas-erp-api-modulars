<?php

/**
 * Created by Reliese Model.
 */

namespace App\GeneralModule\Models;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class BugReport
 *
 * @property int $id
 * @property int $company_id
 * @property int $user_id
 * @property string $title
 * @property bool $status
 * @property string $message
 * @property string $platform
 * @property string $image
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class BugReport extends Model
{
	protected $table = 'bug_reports';

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
		'message',
		'platform',
		'image'
	];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
