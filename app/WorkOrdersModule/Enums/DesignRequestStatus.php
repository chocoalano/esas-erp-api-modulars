<?php
namespace App\WorkOrdersModule\Enums;

enum DesignRequestStatus: string
{
    case DRAFT = 'DRAFT';
    case SUBMITTED = 'SUBMITTED';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';
    case IN_PROGRESS = 'IN_PROGRESS';
    case DONE = 'DONE';
    case CANCELED = 'CANCELED';
}
