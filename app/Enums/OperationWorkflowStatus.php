<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OperationWorkflowStatus: string implements HasColor, HasLabel
{
    /**
     * Unified operational workflow lifecycle statuses.
     */
    case NEW = 'new';
    case IN_PROGRESS = 'in_progress';
    case EMPLOYEE_CUSTODY = 'employee_custody';
    case AT_TREASURY = 'at_treasury';
    case AWAITING_APPROVAL = 'awaiting_approval';
    case MISMATCHED = 'mismatched';
    case SUSPENSE_AMOUNT = 'suspense_amount';
    case ESCALATED = 'escalated';
    case RETROACTIVE_SETTLEMENT = 'retroactive_settlement';
    case COMPLETED = 'completed';

    public function getLabel(): string
    {
        return match ($this) {
            self::NEW => __('services_tracking.workflow_statuses.new'),
            self::IN_PROGRESS => __('services_tracking.workflow_statuses.in_progress'),
            self::EMPLOYEE_CUSTODY => __('services_tracking.workflow_statuses.employee_custody'),
            self::AT_TREASURY => __('services_tracking.workflow_statuses.at_treasury'),
            self::AWAITING_APPROVAL => __('services_tracking.workflow_statuses.awaiting_approval'),
            self::MISMATCHED => __('services_tracking.workflow_statuses.mismatched'),
            self::SUSPENSE_AMOUNT => __('services_tracking.workflow_statuses.suspense_amount'),
            self::ESCALATED => __('services_tracking.workflow_statuses.escalated'),
            self::RETROACTIVE_SETTLEMENT => __('services_tracking.workflow_statuses.retroactive_settlement'),
            self::COMPLETED => __('services_tracking.workflow_statuses.completed'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::NEW => 'info',
            self::IN_PROGRESS => 'primary',
            self::EMPLOYEE_CUSTODY, self::RETROACTIVE_SETTLEMENT => 'warning',
            self::AT_TREASURY => 'info',
            self::AWAITING_APPROVAL => 'warning',
            self::MISMATCHED, self::SUSPENSE_AMOUNT => 'danger',
            self::ESCALATED => 'danger',
            self::COMPLETED => 'success',
        };
    }
}
