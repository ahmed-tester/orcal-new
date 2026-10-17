<?php

namespace App\Observers;

use App\Models\Company;
use App\Models\User;
use App\Models\WeeklyNote;
use Illuminate\Support\Facades\Auth;

class WeeklyNoteObserver
{
    /**
     * Handle the WeeklyNote "creating" event.
     */
    public function creating(WeeklyNote $note): void
    {
        if (empty($note->voucher_number)) {
            $note->voucher_number = $this->generateVoucherNumber($note->company_id);
        }

        if (empty($note->created_by)) {
            $note->created_by = Auth::id();
        }
    }

    /**
     * Generate unique voucher number.
     */
    public function generateVoucherNumber(?int $companyId = null): string
    {
        $company = $companyId ? Company::find($companyId) : null;

        if (!$company) {
            $user = Auth::user();
            if ($user) {
                $company = $user->employee?->company;
                if (!$company && method_exists($user, 'isSuperAdminOrOwner') && $user->isSuperAdminOrOwner()) {
                    $company = Company::first();
                }
            }
        }

        if (!$company) {
            $company = Company::first();
        }

        $companyPrefix = $company?->company_number ?: ($company ? 'COM-' . $company->id : 'ORG');
        $nextNumber = (WeeklyNote::max('id') ?? 0) + 1;

        return $companyPrefix . '-WN-' . date('Ymd') . '-' . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
