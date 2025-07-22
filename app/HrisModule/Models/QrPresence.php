<?php

/**
 * Created by Reliese Model.
 */

namespace App\HrisModule\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class QrPresence
 *
 * @property int $id
 * @property string $type
 * @property int $departement_id
 * @property int $timework_id
 * @property string $token
 * @property Carbon $for_presence
 * @property Carbon $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class QrPresence extends Model
{
	protected $table = 'qr_presences';

	protected $casts = [
		'departement_id' => 'int',
		'timework_id' => 'int',
		'for_presence' => 'datetime',
		'expires_at' => 'datetime'
	];

	protected $hidden = [
		'token'
	];

	protected $fillable = [
		'type',
		'departement_id',
		'timework_id',
		'token',
		'for_presence',
		'expires_at'
	];

    public function transactions()
    {
        return $this->hasOne(QrPresenceTransaction::class, 'qr_presence_id');
    }

    public function departement()
    {
        return $this->belongsTo(Departement::class, 'id');
    }
    public function timework()
    {
        return $this->belongsTo(TimeWorke::class, 'id');
    }
}
