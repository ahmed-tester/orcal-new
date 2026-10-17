<?php

namespace App\Services;

use App\Models\Guarantor;

class GuarantorService
{
    /**
     * Resolve and persist guarantor data from form input.
     */
    public static function resolveGuarantor(array $data, ?int $companyId = null): ?Guarantor
    {
        $option = $data['guarantor_option'] ?? 'none';

        if ($option === 'none' || empty($option)) {
            return null;
        }

        if ($option === 'internal' && !empty($data['guarantor_employee_id'])) {
            $guarantor = Guarantor::firstOrNew([
                'type' => 'internal',
                'employee_id' => $data['guarantor_employee_id'],
            ]);

            $emp = \App\Models\Employee::find($data['guarantor_employee_id']);
            $guarantorCompanyId = $emp?->company_id ?? ($data['guarantor_company_id'] ?? $companyId);

            $guarantor->company_id = $guarantorCompanyId ?? $guarantor->company_id;
            $guarantor->name = $data['guarantor_name'] ?? $guarantor->name ?? __('guarantors.type_badges.internal');
            $guarantor->national_id = $data['guarantor_national_id'] ?? $guarantor->national_id;
            $guarantor->phone = PhoneService::normalize($data['guarantor_phone'] ?? null) ?: $guarantor->phone;
            $guarantor->nationality = $data['guarantor_nationality'] ?? $guarantor->nationality;
            $guarantor->job_title = $data['guarantor_job_title'] ?? $guarantor->job_title;
            $guarantor->address = $data['guarantor_address'] ?? $guarantor->address;
            $guarantor->notes = $data['guarantor_notes'] ?? $guarantor->notes;
            $guarantor->status = 'active';
            $guarantor->save();

            return $guarantor;
        }

        if ($option === 'external' && !empty($data['guarantor_name'])) {
            $query = Guarantor::where('type', 'external');
            if (!empty($data['guarantor_national_id'])) {
                $query->where('national_id', $data['guarantor_national_id']);
            } elseif (!empty($data['guarantor_phone'])) {
                $query->where('phone', PhoneService::normalize($data['guarantor_phone']));
            } else {
                $query->where('name', $data['guarantor_name']);
            }

            $guarantor = $query->first() ?? new Guarantor(['type' => 'external']);

            $guarantor->company_id = $companyId ?? $guarantor->company_id;
            $guarantor->name = $data['guarantor_name'];
            $guarantor->national_id = $data['guarantor_national_id'] ?? $guarantor->national_id;
            $guarantor->phone = PhoneService::normalize($data['guarantor_phone'] ?? null) ?: $guarantor->phone;
            $guarantor->nationality = $data['guarantor_nationality'] ?? $guarantor->nationality;
            $guarantor->job_title = $data['guarantor_job_title'] ?? $guarantor->job_title;
            $guarantor->address = $data['guarantor_address'] ?? $guarantor->address;
            $guarantor->notes = $data['guarantor_notes'] ?? $guarantor->notes;
            $guarantor->status = 'active';
            $guarantor->save();

            return $guarantor;
        }

        return null;
    }
}
