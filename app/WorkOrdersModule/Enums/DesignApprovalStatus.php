<?php
namespace App\WorkOrdersModule\Enums;

enum DesignApprovalStatus: string
{
    case PENDING = 'PENDING';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';
}
