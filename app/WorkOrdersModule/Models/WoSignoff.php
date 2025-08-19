<?php
namespace App\WorkOrdersModule\Models;

use App\GeneralModule\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WoSignoff extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_order_id','done_by_id','head_maintenance_id','requester_verify_id','notes','signed_at',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function workOrder(): BelongsTo { return $this->belongsTo(WorkOrder::class); }
    public function doneBy(): BelongsTo { return $this->belongsTo(User::class, 'done_by_id'); }
    public function headMaintenance(): BelongsTo { return $this->belongsTo(User::class, 'head_maintenance_id'); }
    public function requesterVerify(): BelongsTo { return $this->belongsTo(User::class, 'requester_verify_id'); }
}
