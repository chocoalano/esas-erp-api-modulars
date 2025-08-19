<?php
namespace App\WorkOrdersModule\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DesignRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'design_request_id','line_no','kebutuhan','isi_konten','ukuran','referensi','keterangan',
    ];

    public function designRequest(): BelongsTo
    {
        return $this->belongsTo(DesignRequest::class);
    }
}
