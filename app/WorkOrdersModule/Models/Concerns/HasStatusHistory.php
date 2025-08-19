<?php
namespace App\WorkOrdersModule\Models\Concerns;

use App\WorkOrdersModule\Models\StatusHistory;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasStatusHistory
{
    abstract public static function statusOwnerType(): string;

    public function statusHistories(): HasMany
    {
        return $this->hasMany(StatusHistory::class, 'owner_id')
            ->where('owner_type', static::statusOwnerType())
            ->orderByDesc('changed_at');
    }

    public function addStatusHistory(string $from, string $to, ?int $byUserId = null, ?string $remarks = null): StatusHistory
    {
        /** @var \Illuminate\Database\Eloquent\Model $this */
        return $this->statusHistories()->create([
            'owner_type'   => static::statusOwnerType(), // redundant for safety
            'owner_id'     => $this->getKey(),
            'from_status'  => $from,
            'to_status'    => $to,
            'changed_by_id'=> $byUserId,
            'remarks'      => $remarks,
        ]);
    }
}
