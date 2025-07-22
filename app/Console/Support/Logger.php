<?php

namespace App\Console\Support;

use App\GeneralModule\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use InvalidArgumentException;

class Logger
{
    public static function log(?string $action = null, Model $model, array $payload = []): void
    {
        if (!$model instanceof Model) {
            throw new InvalidArgumentException('Logger: model must be an instance of Eloquent Model');
        }

        ActivityLog::create([
            'user_id'    => Auth::id(),
            'method'     => Request::method(),
            'url'        => Request::fullUrl(),
            'action'     => $action,
            'model_type' => $model->getMorphClass(),
            'model_id'   => $model->getKey(),
            'payload'    => json_encode(!empty($payload) ? $payload : $model->toArray()),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
