<?php
namespace App\WorkOrdersModule\Models;

use App\HrisModule\Models\Departement;
use App\WorkOrdersModule\Enums\WorkOrderStatus;
use App\WorkOrdersModule\Models\Concerns\HasStatusHistory;
use App\WorkOrdersModule\Models\Concerns\HasTypedAttachments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, HasOne};
use App\GeneralModule\Models\User;

class WorkOrder extends Model
{
    use HasFactory, HasTypedAttachments, HasStatusHistory;

    protected $fillable = [
        'wo_no',
        'requested_by_id',
        'request_date',
        'department_provides',
        'department_id',
        'area',
        'complaint',
        'asset_info',
        'status',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'request_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'status' => WorkOrderStatus::class,
    ];

    public static function attachmentOwnerType(): string
    {
        return 'WORK_ORDER';
    }
    public static function statusOwnerType(): string
    {
        return 'WORK_ORDER';
    }

    // Relations
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }
    public function department(): BelongsTo
    {
        return $this->belongsTo(Departement::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(WoService::class);
    }
    public function spareparts(): HasMany
    {
        return $this->hasMany(WoSparepart::class);
    }

    // Clearances & signoff cenderung 1 record per WO.
    public function clearance(): HasOne
    {
        return $this->hasOne(WoClearance::class)->latestOfMany();
    }
    public function signoff()
    {
        return $this->hasMany(WoSignoff::class, 'work_order_id');
    }

    public function latestSignoff()
    {
        return $this->hasOne(WoSignoff::class, 'work_order_id')->latestOfMany();
    }

    // Scopes
    public function scopeStatus($q, WorkOrderStatus|string $status)
    {
        return $q->where('status', $status instanceof WorkOrderStatus ? $status->value : $status);
    }

    public function changeStatus(WorkOrderStatus $to, ?int $byUserId = null, ?string $remarks = null): void
    {
        $from = $this->status?->value ?? null;
        $this->update(['status' => $to]);
        $this->addStatusHistory($from ?? '', $to->value, $byUserId, $remarks);
    }

    public static function generateRequestNumber(int $divisionTargetId, int $divisionId): string
    {
        // Ambil divisi asal dan tujuan
        $division = Departement::findOrFail($divisionId);
        $divisionTarget = Departement::findOrFail($divisionTargetId);

        // Ambil 3 huruf pertama nama divisi (uppercase, tanpa spasi)
        $divisionCode = strtoupper(substr(preg_replace('/\s+/', '', $division->name), 0, 3));
        $divisionTargetCode = strtoupper(substr(preg_replace('/\s+/', '', $divisionTarget->name), 0, 3));

        // Cari nomor terakhir untuk divisi asal
        $lastNumber = self::where('department_id', $divisionId)
            ->whereNotNull('wo_no')
            ->orderByDesc('id')
            ->value('wo_no');

        $sequence = 1;

        if ($lastNumber) {
            // Ambil angka terakhir dari format: SAS/WO/XXX/XXX/NNN
            $parts = explode('/', $lastNumber);
            $lastSequence = end($parts);

            if (is_numeric($lastSequence)) {
                $sequence = (int) $lastSequence + 1;
            }
        }

        // Format urut dengan leading zero (misal: 001, 002, 010)
        $sequenceFormatted = str_pad($sequence, 3, '0', STR_PAD_LEFT);

        // Format: SAS/WO/{divisi_asal}/{divisi_tujuan}/{nomor_urut}
        return sprintf("SAS/WO/%s/%s/%s", $divisionCode, $divisionTargetCode, $sequenceFormatted);
    }
}
