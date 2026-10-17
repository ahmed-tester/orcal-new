<?php

namespace App\Enums;

use App\Models\LeaveType;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum AnnualLeaveType: string implements HasLabel, HasColor, HasIcon
{
    case DEDICATED_ANNUAL = 'dedicated_annual';

    public const DEDICATED_NAME = 'رصيد الإجازة السنوية (المخصص للموظف)';

    public function getLabel(): string
    {
        return match ($this) {
            self::DEDICATED_ANNUAL => __('leaves.fields.dedicated_annual_leave'),
        };
    }

    public function getDatabaseName(): string
    {
        return match ($this) {
            self::DEDICATED_ANNUAL => self::DEDICATED_NAME,
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::DEDICATED_ANNUAL => 'primary',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::DEDICATED_ANNUAL => 'heroicon-m-star',
        };
    }

    /**
     * Resolve the underlying LeaveType model ID for this enum case.
     */
    public function getLeaveTypeId(): ?int
    {
        static $cachedIds = [];

        if (isset($cachedIds[$this->value])) {
            return $cachedIds[$this->value];
        }

        $type = LeaveType::where('name', $this->getDatabaseName())
            ->orWhere('name', 'like', '%المخصص للموظف%')
            ->first();

        if (!$type) {
            $type = LeaveType::firstOrCreate(
                ['name' => $this->getDatabaseName()],
                [
                    'annual_balance' => 0,
                    'is_paid' => true,
                    'requires_approval' => true,
                    'status' => \App\Enums\GeneralStatus::ACTIVE,
                ]
            );
        }

        return $cachedIds[$this->value] = $type?->id;
    }

    /**
     * Check if a given leave type ID matches this enum case.
     */
    public function matches(?int $leaveTypeId): bool
    {
        return $leaveTypeId !== null && $leaveTypeId === $this->getLeaveTypeId();
    }
}
