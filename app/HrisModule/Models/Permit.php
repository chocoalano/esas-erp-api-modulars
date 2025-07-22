<?php

namespace App\HrisModule\Models;

use App\GeneralModule\Models\User; // Assuming this is correct for your User model
use App\HrisModule\Models\PermitType; // Ensure this model exists and path is correct
use App\HrisModule\Models\PermitApprove; // Ensure this model exists and path is correct
use App\HrisModule\Models\UserTimeworkSchedule; // Ensure this model exists and path is correct
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Permit
 *
 * @property int $id
 * @property string $permit_numbers
 * @property int $user_id
 * @property int $permit_type_id
 * @property int $user_timework_schedule_id
 * @property Carbon|null $timein_adjust
 * @property Carbon|null $timeout_adjust
 * @property int|null $current_shift_id
 * @property int|null $adjust_shift_id
 * @property string|null $start_date           // Stored as date, but accessed as Y-m-d string
 * @property string|null $end_date             // Stored as date, but accessed as Y-m-d string
 * @property string|null $start_time           // Stored as datetime, but accessed as H:i:s string
 * @property string|null $end_time             // Stored as datetime, but accessed as H:i:s string
 * @property string|null $notes
 * @property string|null $file
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @property-read User $user
 * @property-read PermitType $permitType
 * @property-read UserTimeworkSchedule $userTimeworkSchedule
 * @property-read \Illuminate\Database\Eloquent\Collection|PermitApprove[] $approvals
 *
 * @package App\HrisModule\Models
 */
class Permit extends Model
{
    use SoftDeletes;

    protected $table = 'permits';

    protected $casts = [
        'user_id' => 'integer', // Changed to 'integer' for strictness
        'permit_type_id' => 'integer',
        'user_timework_schedule_id' => 'integer',
        'timein_adjust' => 'datetime',
        'timeout_adjust' => 'datetime',
        'current_shift_id' => 'integer',
        'adjust_shift_id' => 'integer',
        'start_date' => 'date',   // Cast as 'date' for Y-m-d storage
        'end_date' => 'date',     // Cast as 'date' for Y-m-d storage
        'start_time' => 'datetime', // Cast as 'datetime' for full compatibility with Carbon, will use accessor for H:i:s
        'end_time' => 'datetime'    // Cast as 'datetime', will use accessor for H:i:s
    ];

    protected $fillable = [
        'permit_numbers',
        'user_id',
        'permit_type_id',
        'user_timework_schedule_id',
        'timein_adjust',
        'timeout_adjust',
        'current_shift_id',
        'adjust_shift_id',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'notes',
        'file'
    ];

    // --- Accessors for Date and Time Formatting ---

    /**
     * Get the start date in 'YYYY-MM-DD' format.
     * @param string|null $value
     * @return string|null
     */
    public function getStartDateAttribute(?string $value): ?string
    {
        return $value ? Carbon::parse($value)->format('Y-m-d') : null;
    }

    /**
     * Get the end date in 'YYYY-MM-DD' format.
     * @param string|null $value
     * @return string|null
     */
    public function getEndDateAttribute(?string $value): ?string
    {
        return $value ? Carbon::parse($value)->format('Y-m-d') : null;
    }

    /**
     * Get the start time in 'HH:MM:SS' format.
     * @param string|null $value
     * @return string|null
     */
    public function getStartTimeAttribute(?string $value): ?string
    {
        return $value ? Carbon::parse($value)->format('H:i:s') : null;
    }

    /**
     * Get the end time in 'HH:MM:SS' format.
     * @param string|null $value
     * @return string|null
     */
    public function getEndTimeAttribute(?string $value): ?string
    {
        return $value ? Carbon::parse($value)->format('H:i:s') : null;
    }

    /**
     * Get the created_at timestamp in 'DD Month YY HH:MM:SS' format.
     * @param string|null $value
     * @return string|null
     */
    public function getCreatedAtAttribute(?string $value): ?string
    {
        return $value ? Carbon::parse($value)->format('d F y H:i:s') : null;
    }

    /**
     * Get the updated_at timestamp in 'DD Month YY HH:MM:SS' format.
     * @param string|null $value
     * @return string|null
     */
    public function getUpdatedAtAttribute(?string $value): ?string
    {
        return $value ? Carbon::parse($value)->format('d F y H:i:s') : null;
    }

    // --- Relationships ---

    /**
     * Get the user that owns the permit.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get the permit type associated with the permit.
     */
    public function permitType(): BelongsTo
    {
        return $this->belongsTo(PermitType::class, 'permit_type_id', 'id');
    }

    /**
     * Get the approvals for the permit.
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(PermitApprove::class, 'permit_id', 'id'); // Assuming foreign key is permit_id
    }

    /**
     * Get the user timework schedule associated with the permit.
     */
    public function userTimeworkSchedule(): BelongsTo
    {
        return $this->belongsTo(UserTimeworkSchedule::class, 'user_timework_schedule_id', 'id');
    }
}
