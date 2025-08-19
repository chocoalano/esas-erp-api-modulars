<?php
namespace App\WorkOrdersModule\Models\Concerns;
use App\WorkOrdersModule\Models\Attachment;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasTypedAttachments
{
    abstract public static function attachmentOwnerType(): string;
    abstract public function getKeyName();

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'owner_id')
            ->where('owner_type', static::attachmentOwnerType());
    }
}
