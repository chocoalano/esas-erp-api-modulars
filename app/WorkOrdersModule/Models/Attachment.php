<?php
namespace App\WorkOrdersModule\Models;

use App\GeneralModule\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Attachment extends Model
{
    use HasFactory;

    public const TYPE_DESIGN_REQUEST = 'DESIGN_REQUEST';
    public const TYPE_WORK_ORDER     = 'WORK_ORDER';

    protected $fillable = [
        'owner_type','owner_id','file_name','file_path','url','meta','uploaded_by_id','uploaded_at',
    ];

    protected $casts = [
        'meta'        => 'array',
        'uploaded_at' => 'datetime',
    ];

    // Helper scopes
    public function scopeForDesignRequest(Builder $q, int $designRequestId): Builder
    {
        return $q->where('owner_type', self::TYPE_DESIGN_REQUEST)->where('owner_id', $designRequestId);
    }

    public function scopeForWorkOrder(Builder $q, int $workOrderId): Builder
    {
        return $q->where('owner_type', self::TYPE_WORK_ORDER)->where('owner_id', $workOrderId);
    }

    // (Opsional) relasi ke uploader
    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }
}
