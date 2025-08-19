<?php
namespace App\WorkOrdersModule\Models;

use App\GeneralModule\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatusHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_type','owner_id','from_status','to_status','changed_by_id','changed_at','remarks',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    // (Opsional) relasi ke user/employee yang mengubah
    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by_id');
    }
}
