<?php
namespace App\WorkOrdersModule\Models;

use App\GeneralModule\Models\User;
use App\WorkOrdersModule\Enums\DesignApprovalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DesignApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'design_request_id','approver_id','status','decided_at','remarks',
    ];

    protected $casts = [
        'status'     => DesignApprovalStatus::class,
        'decided_at' => 'datetime',
    ];

    public function designRequest(): BelongsTo
    {
        return $this->belongsTo(DesignRequest::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
