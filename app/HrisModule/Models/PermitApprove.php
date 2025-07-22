<?php

namespace App\HrisModule\Models;

use App\GeneralModule\Models\User;
use App\Notifications\Approval;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class PermitApprove
 *
 * @property int $id
 * @property int $permit_id
 * @property int $user_id
 * @property string $user_type
 * @property string $user_approve
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class PermitApprove extends Model
{
    protected $table = 'permit_approves';

    protected $casts = [
        'permit_id' => 'int',
        'user_id' => 'int',
    ];

    protected $fillable = [
        'permit_id',
        'user_id',
        'user_type',
        'user_approve',
        'notes',
    ];

    public function permit(): BelongsTo
    {
        return $this->belongsTo(Permit::class, 'permit_id');
    }

    protected static function booted(): void
    {
        static::created(function (self $permitApprove) {
            $user = User::find($permitApprove->user_id);
            $requesterName = $permitApprove->permit->user?->name ?? 'Seorang karyawan';

            if ($user) {
                $user->notify(new Approval(
                    title: 'Izin Baru Diajukan',
                    message: "$requesterName mengajukan izin dan menunggu persetujuan Anda.",
                    url: route('permits.show', $permitApprove->permit_id)
                ));
            }
        });
    }
}
