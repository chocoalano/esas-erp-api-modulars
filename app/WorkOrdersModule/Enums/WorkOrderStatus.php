<?php
namespace App\WorkOrdersModule\Enums;

enum WorkOrderStatus: string
{
    case OPEN = 'OPEN';
    case IN_PROGRESS = 'IN_PROGRESS';
    case ON_HOLD = 'ON_HOLD';
    case DONE = 'DONE';
    case CANCELED = 'CANCELED';
}
