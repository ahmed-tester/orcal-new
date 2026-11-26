<?php

namespace App\Enums\Payroll;

enum FinancialApprovalItemStatus: string
{
    case PENDING = 'pending';
    case EXECUTED = 'executed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
}
