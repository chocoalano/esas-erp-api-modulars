<?php
namespace App\WorkOrdersModule\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WoSparepart extends Model
{
    use HasFactory;

    protected $fillable = ['work_order_id','part_name','quantity','remarks'];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function workOrder(): BelongsTo { return $this->belongsTo(WorkOrder::class); }
}
