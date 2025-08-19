<?php
namespace App\WorkOrdersModule\Enums;

enum DesignRequestPriority: string
{
    case HIGH = 'high';
    case MEDIUM = 'medium';
    case LOW = 'low';
}
