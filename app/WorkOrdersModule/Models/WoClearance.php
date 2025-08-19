<?php
namespace App\WorkOrdersModule\Models;

use App\GeneralModule\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WoClearance extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_order_id','hygiene_clearance','maintenance_clearance',
        'verified_by_id','verified_at',
    ];

    protected $casts = [
        'hygiene_clearance'     => 'boolean',
        'maintenance_clearance' => 'boolean',
        'verified_at'           => 'datetime',
    ];

    public function workOrder(): BelongsTo { return $this->belongsTo(WorkOrder::class); }
    public function verifiedBy(): BelongsTo { return $this->belongsTo(User::class, 'verified_by_id'); }
}
