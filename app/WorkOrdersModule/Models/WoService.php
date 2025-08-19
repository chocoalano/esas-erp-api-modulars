<?php
namespace App\WorkOrdersModule\Models;

use App\GeneralModule\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WoService extends Model
{
    // Hanya created_at, no updated_at
    public $timestamps = false;
    const CREATED_AT = 'created_at';

    protected $fillable = ['work_order_id','description','created_by_id','created_at'];

    protected $casts = ['created_at' => 'datetime'];

    public function workOrder(): BelongsTo { return $this->belongsTo(WorkOrder::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by_id'); }
}
