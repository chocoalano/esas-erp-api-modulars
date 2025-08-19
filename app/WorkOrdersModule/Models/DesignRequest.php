<?php
namespace App\WorkOrdersModule\Models;

use App\HrisModule\Models\Departement;
use App\WorkOrdersModule\Enums\DesignRequestPriority;
use App\WorkOrdersModule\Enums\DesignRequestStatus;
use App\WorkOrdersModule\Models\Concerns\HasStatusHistory;
use App\WorkOrdersModule\Models\Concerns\HasTypedAttachments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use App\GeneralModule\Models\User;

class DesignRequest extends Model
{
    use HasFactory, HasTypedAttachments, HasStatusHistory;

    protected $fillable = [
        'request_no','request_date','need_by_date','priority',
        'pic_id','division_id','submitted_to_id','acknowledged_by_id',
        'status','notes',
    ];

    protected $casts = [
        'request_date' => 'date',
        'need_by_date' => 'date',
        'priority'     => DesignRequestPriority::class,
        'status'       => DesignRequestStatus::class,
    ];

    // owner & status types for traits
    public static function attachmentOwnerType(): string { return 'DESIGN_REQUEST'; }
    public static function statusOwnerType(): string { return 'DESIGN_REQUEST'; }
    public function pic(): BelongsTo { return $this->belongsTo(User::class, 'pic_id'); }
    public function division(): BelongsTo { return $this->belongsTo(Departement::class); }
    public function submittedTo(): BelongsTo { return $this->belongsTo(User::class, 'submitted_to_id'); }
    public function acknowledgedBy(): BelongsTo { return $this->belongsTo(User::class, 'acknowledged_by_id'); }

    public function items(): HasMany { return $this->hasMany(DesignRequestItem::class); }
    public function approvals(): HasMany { return $this->hasMany(DesignApproval::class); }

    // Scopes
    public function scopeStatus($q, DesignRequestStatus|string $status)
    {
        return $q->where('status', $status instanceof DesignRequestStatus ? $status->value : $status);
    }

    // Workflow helper (mencatat status history)
    public function changeStatus(DesignRequestStatus $to, ?int $byUserId = null, ?string $remarks = null): void
    {
        $from = $this->status?->value ?? null;
        $this->update(['status' => $to]);
        $this->addStatusHistory($from ?? '', $to->value, $byUserId, $remarks);
    }

    /**
     * Generate request number dengan format:
     * SAS/FORM/(3 huruf singkatan divisi)/(nomor urut)
     */
    public static function generateRequestNumber(int $divisionId): string
    {
        // Ambil divisi
        $division = Departement::findOrFail($divisionId);

        // Ambil 3 huruf pertama nama divisi (uppercase)
        $divisionCode = strtoupper(substr(preg_replace('/\s+/', '', $division->name), 0, 3));

        // Cari nomor terakhir untuk divisi ini
        $lastNumber = self::where('division_id', $divisionId)
            ->whereNotNull('request_no')
            ->orderByDesc('id')
            ->value('request_no');

        $sequence = 1;

        if ($lastNumber) {
            // Ambil angka terakhir dari format SAS/FORM/XXX/NNN
            $parts = explode('/', $lastNumber);
            if (isset($parts[3]) && is_numeric($parts[3])) {
                $sequence = (int)$parts[3] + 1;
            }
        }

        // Format: SAS/FORM/MTC/1
        return sprintf("SAS/FORM/%s/%d", $divisionCode, $sequence);
    }
}
