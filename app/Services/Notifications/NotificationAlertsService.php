<?php

namespace App\Services\Notifications;

use App\Enums\ViolationPaymentStatus;
use App\Enums\ViolationStage;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeDetail;
use App\Models\Invoice;
use App\Models\Leave;
use App\Models\Task;
use App\Models\Violation;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class NotificationAlertsService
{
    /**
     * Fetch all active alerts (documents expiry, tasks, invoices, violations) within an alert window.
     */
    public function getActiveAlerts(
        ?string $category = null,
        ?string $urgency = null,
        ?string $search = null
    ): Collection {
        $alerts = collect();
        $now = Carbon::today();
        $horizonDate = $now->copy()->addDays(90);

        try {
            // 1. Employee Documents Expiry
            $details = EmployeeDetail::with(['employee.user', 'employee.company', 'employee.jobTitle'])->get();

            foreach ($details as $detail) {
                $employee = $detail->employee;
                if (!$employee || ($employee->status && $employee->status->value !== 'active')) {
                    continue;
                }

                $empName = $employee->user?->name ?? __('notifications.alerts.employee_fallback', ['id' => $employee->id]);
                $empPhone = $employee->user?->phone ?? $detail->emergency_contact_phone;
                $notifiable = $employee->user ?? $employee;

                // A) Iqama / Residency
                if ($detail->iqama_expiry) {
                    $expiry = Carbon::parse($detail->iqama_expiry);
                    if ($expiry->lessThanOrEqualTo($horizonDate)) {
                        $alerts->push($this->formatAlert(
                            id: 'iqama_' . $detail->id,
                            category: 'documents',
                            type: 'iqama',
                            typeLabel: __('notifications.alerts.expiry_label', ['document' => __('notifications.documents.iqama')]),
                            title: $empName,
                            subtitle: __('notifications.alerts.iqama_subtitle', ['number' => $detail->iqama_number ?? '—']),
                            dueDate: $expiry,
                            notifiable: $notifiable,
                            recipientName: $empName,
                            recipientPhone: $empPhone,
                            companyName: $employee->company?->trade_name
                        ));
                    }
                }

                // B) Passport
                if ($detail->passport_expiry) {
                    $expiry = Carbon::parse($detail->passport_expiry);
                    if ($expiry->lessThanOrEqualTo($horizonDate)) {
                        $alerts->push($this->formatAlert(
                            id: 'passport_' . $detail->id,
                            category: 'documents',
                            type: 'passport',
                            typeLabel: __('notifications.alerts.expiry_label', ['document' => __('notifications.documents.passport')]),
                            title: $empName,
                            subtitle: __('notifications.alerts.passport_subtitle', ['number' => $detail->passport_number ?? '—']),
                            dueDate: $expiry,
                            notifiable: $notifiable,
                            recipientName: $empName,
                            recipientPhone: $empPhone,
                            companyName: $employee->company?->trade_name
                        ));
                    }
                }

                // C) Health Insurance
                if ($detail->insurance_expiry) {
                    $expiry = Carbon::parse($detail->insurance_expiry);
                    if ($expiry->lessThanOrEqualTo($horizonDate)) {
                        $alerts->push($this->formatAlert(
                            id: 'insurance_' . $detail->id,
                            category: 'documents',
                            type: 'insurance',
                            typeLabel: __('notifications.alerts.expiry_label', ['document' => __('notifications.documents.health_insurance')]),
                            title: $empName,
                            subtitle: __('notifications.alerts.insurance_subtitle'),
                            dueDate: $expiry,
                            notifiable: $notifiable,
                            recipientName: $empName,
                            recipientPhone: $empPhone,
                            companyName: $employee->company?->trade_name
                        ));
                    }
                }

                // D) Work Permit
                if ($detail->work_permit_expiry) {
                    $expiry = Carbon::parse($detail->work_permit_expiry);
                    if ($expiry->lessThanOrEqualTo($horizonDate)) {
                        $alerts->push($this->formatAlert(
                            id: 'work_permit_' . $detail->id,
                            category: 'documents',
                            type: 'work_permit',
                            typeLabel: __('notifications.alerts.expiry_label', ['document' => __('notifications.documents.work_permit')]),
                            title: $empName,
                            subtitle: __('notifications.alerts.work_permit_subtitle', ['number' => $detail->work_permit_number ?? '—']),
                            dueDate: $expiry,
                            notifiable: $notifiable,
                            recipientName: $empName,
                            recipientPhone: $empPhone,
                            companyName: $employee->company?->trade_name
                        ));
                    }
                }

                // E) Contract End Date
                if ($employee->contract_end_date) {
                    $expiry = Carbon::parse($employee->contract_end_date);
                    if ($expiry->lessThanOrEqualTo($horizonDate)) {
                        $alerts->push($this->formatAlert(
                            id: 'contract_' . $employee->id,
                            category: 'documents',
                            type: 'contract',
                            typeLabel: __('notifications.alerts.expiry_label', ['document' => __('notifications.documents.contract')]),
                            title: $empName,
                            subtitle: __('notifications.alerts.contract_subtitle'),
                            dueDate: $expiry,
                            notifiable: $notifiable,
                            recipientName: $empName,
                            recipientPhone: $empPhone,
                            companyName: $employee->company?->trade_name
                        ));
                    }
                }
            }

            // 1.2 Company Licenses & Registrations Expiry
            $companies = Company::where(function ($q) {
                $q->whereNotNull('commercial_registration_expiry')
                  ->orWhereNotNull('municipality_license_expiry')
                  ->orWhereNotNull('civil_defense_license_expiry');
            })->get();

            foreach ($companies as $company) {
                $compName = $company->trade_name ?? $company->name ?? __('notifications.alerts.company_fallback');
                $ownerNotifiable = $company->owner?->user ?? $company;

                if ($company->commercial_registration_expiry) {
                    $expiry = Carbon::parse($company->commercial_registration_expiry);
                    if ($expiry->lessThanOrEqualTo($horizonDate)) {
                        $alerts->push($this->formatAlert(
                            id: 'cr_' . $company->id,
                            category: 'documents',
                            type: 'cr',
                            typeLabel: __('notifications.alerts.expiry_label', ['document' => __('notifications.documents.cr')]),
                            title: $compName,
                            subtitle: __('notifications.alerts.cr_subtitle', ['number' => $company->commercial_registration_number ?? '—']),
                            dueDate: $expiry,
                            notifiable: $ownerNotifiable,
                            recipientName: $compName,
                            recipientPhone: $company->phone,
                            companyName: $compName
                        ));
                    }
                }

                if ($company->municipality_license_expiry) {
                    $expiry = Carbon::parse($company->municipality_license_expiry);
                    if ($expiry->lessThanOrEqualTo($horizonDate)) {
                        $alerts->push($this->formatAlert(
                            id: 'municipality_' . $company->id,
                            category: 'documents',
                            type: 'municipality',
                            typeLabel: __('notifications.alerts.expiry_label', ['document' => __('notifications.documents.municipality')]),
                            title: $compName,
                            subtitle: __('notifications.alerts.municipality_subtitle', ['number' => $company->municipality_license_number ?? '—']),
                            dueDate: $expiry,
                            notifiable: $ownerNotifiable,
                            recipientName: $compName,
                            recipientPhone: $company->phone,
                            companyName: $compName
                        ));
                    }
                }

                if ($company->civil_defense_license_expiry) {
                    $expiry = Carbon::parse($company->civil_defense_license_expiry);
                    if ($expiry->lessThanOrEqualTo($horizonDate)) {
                        $alerts->push($this->formatAlert(
                            id: 'civil_defense_' . $company->id,
                            category: 'documents',
                            type: 'civil_defense',
                            typeLabel: __('notifications.alerts.expiry_label', ['document' => __('notifications.documents.civil_defense')]),
                            title: $compName,
                            subtitle: __('notifications.alerts.civil_defense_subtitle', ['number' => $company->civil_defense_license_number ?? '—']),
                            dueDate: $expiry,
                            notifiable: $ownerNotifiable,
                            recipientName: $compName,
                            recipientPhone: $company->phone,
                            companyName: $compName
                        ));
                    }
                }
            }

            // 1.3 Overdue Leaves Alert
            if (class_exists(Leave::class)) {
                $overdueLeaves = Leave::with(['employee.user', 'employee.company'])
                    ->where('status', \App\Enums\LeaveStatus::APPROVED)
                    ->whereNull('actual_return_date')
                    ->where('end_date', '<', $now->format('Y-m-d'))
                    ->get();

                foreach ($overdueLeaves as $leave) {
                    $employee = $leave->employee;
                    if (!$employee) continue;
                    $empName = $employee->user?->name ?? __('notifications.alerts.employee_fallback', ['id' => $employee->id]);
                    $dueDate = Carbon::parse($leave->end_date);

                    $alerts->push($this->formatAlert(
                        id: 'leave_overdue_' . $leave->id,
                        category: 'documents',
                        type: 'leave_overdue',
                        typeLabel: __('notifications.alerts.leave_overdue_label'),
                        title: $empName,
                        subtitle: __('notifications.alerts.leave_overdue_subtitle', ['date' => $dueDate->format('Y-m-d')]),
                        dueDate: $dueDate,
                        notifiable: $employee->user ?? $employee,
                        recipientName: $empName,
                        recipientPhone: $employee->user?->phone,
                        companyName: $employee->company?->trade_name
                    ));
                }
            }

            // 2. Overdue or upcoming Tasks
            if (class_exists(Task::class)) {
                $tasks = Task::with('creator')->whereNotNull('due_date')->get();
                foreach ($tasks as $task) {
                    $dueDate = Carbon::parse($task->due_date);
                    if ($dueDate->lessThanOrEqualTo($horizonDate)) {
                        $alerts->push($this->formatAlert(
                            id: 'task_' . $task->id,
                            category: 'tasks',
                            type: 'task',
                            typeLabel: __('notifications.alerts.task_label'),
                            title: $task->title ?? __('notifications.alerts.task_fallback_title', ['id' => $task->id]),
                            subtitle: __('notifications.alerts.task_priority', ['priority' => $task->priority ?? __('notifications.alerts.task_priority_normal')]),
                            dueDate: $dueDate,
                            notifiable: $task->creator ?? null,
                            recipientName: $task->creator?->name ?? __('notifications.alerts.responsible_fallback'),
                            recipientPhone: $task->creator?->phone ?? null
                        ));
                    }
                }
            }

            // 3. Invoices Due / Overdue
            if (class_exists(Invoice::class)) {
                $invoices = Invoice::with('customer')->whereNotNull('due_date')->where('status', '!=', 'paid')->get();
                foreach ($invoices as $invoice) {
                    $dueDate = Carbon::parse($invoice->due_date);
                    if ($dueDate->lessThanOrEqualTo($horizonDate)) {
                        $alerts->push($this->formatAlert(
                            id: 'invoice_' . $invoice->id,
                            category: 'invoices',
                            type: 'invoice',
                            typeLabel: __('notifications.alerts.invoice_label'),
                            title: __('notifications.alerts.invoice_title', ['number' => $invoice->invoice_number ?? '#' . $invoice->id]),
                            subtitle: __('notifications.alerts.invoice_subtitle', [
                                'customer' => $invoice->customer?->name ?? '—',
                                'amount' => number_format($invoice->total_amount ?? 0, 2),
                                'currency' => __('notifications.alerts.currency'),
                            ]),
                            dueDate: $dueDate,
                            notifiable: $invoice->customer?->user ?? $invoice->customer,
                            recipientName: $invoice->customer?->name ?? __('notifications.alerts.customer_fallback'),
                            recipientPhone: $invoice->customer?->phone ?? null
                        ));
                    }
                }
            }

            // 4. Violations Alerts (Legal Deadline, Admin Execution, Suspended/Payment Due, Deadlines)
            if (class_exists(Violation::class)) {
                $unpaidViolations = Violation::with(['company', 'category', 'type'])
                    ->where('payment_status', '!=', ViolationPaymentStatus::PAID->value)
                    ->get();

                foreach ($unpaidViolations as $violation) {
                    $company = $violation->company;
                    $compName = $company?->trade_name ?? __('notifications.alerts.company_fallback');
                    $ownerNotifiable = $company?->owner?->user ?? $company;
                    $vNumber = $violation->violation_number ?? ($violation->external_violation_number ? "#{$violation->external_violation_number}" : "#{$violation->id}");
                    $remainingFormatted = number_format($violation->remaining_amount, 2) . ' ' . __('violations.transactions.stats.unit');

                    // 4.1 Legal Deadline Alert (Exceeded 60 days from violation date or legal_deadline passed)
                    $daysSinceViolation = $violation->violation_date ? Carbon::parse($violation->violation_date)->diffInDays($now, false) : 0;
                    $isLegalExceeded = $daysSinceViolation > 60 || ($violation->legal_deadline && Carbon::parse($violation->legal_deadline)->isPast());

                    if ($isLegalExceeded || $violation->stage === ViolationStage::LEGAL) {
                        $dueDate = $violation->legal_deadline ? Carbon::parse($violation->legal_deadline) : Carbon::parse($violation->violation_date)->addDays(60);
                        $alerts->push($this->formatAlert(
                            id: 'violation_legal_' . $violation->id,
                            category: 'violations',
                            type: 'legal_deadline',
                            typeLabel: __('violations.stages.legal'),
                            title: __('violations.notifications.legal_deadline_warning_title') . " — {$vNumber}",
                            subtitle: "{$compName} | {$violation->issuing_authority} | " . __('violations.violations.fields.remaining_amount') . ": {$remainingFormatted}",
                            dueDate: $dueDate,
                            notifiable: $ownerNotifiable,
                            recipientName: $compName,
                            recipientPhone: $company?->phone,
                            companyName: $compName
                        ));
                    }

                    // 4.2 Administrative Execution Stage Alert
                    if ($violation->stage === ViolationStage::ADMIN_EXECUTION || ($violation->admin_execution_date && Carbon::parse($violation->admin_execution_date)->lessThanOrEqualTo($horizonDate))) {
                        $dueDate = $violation->admin_execution_date ? Carbon::parse($violation->admin_execution_date) : $now;
                        $alerts->push($this->formatAlert(
                            id: 'violation_admin_exec_' . $violation->id,
                            category: 'violations',
                            type: 'admin_execution',
                            typeLabel: __('violations.stages.admin_execution'),
                            title: __('violations.notifications.admin_execution_title') . " — {$vNumber}",
                            subtitle: "{$compName} | {$violation->issuing_authority} | " . __('violations.violations.fields.remaining_amount') . ": {$remainingFormatted}",
                            dueDate: $dueDate,
                            notifiable: $ownerNotifiable,
                            recipientName: $compName,
                            recipientPhone: $company?->phone,
                            companyName: $compName
                        ));
                    }

                    // 4.3 Suspended / Payment Due Stage Alert
                    if ($violation->stage === ViolationStage::SUSPENDED) {
                        $dueDate = $violation->closure_date ? Carbon::parse($violation->closure_date) : $now;
                        $alerts->push($this->formatAlert(
                            id: 'violation_suspended_' . $violation->id,
                            category: 'violations',
                            type: 'suspended',
                            typeLabel: __('violations.stages.suspended'),
                            title: __('violations.notifications.suspended_title') . " — {$vNumber}",
                            subtitle: "{$compName} | {$violation->issuing_authority} | " . __('violations.violations.fields.remaining_amount') . ": {$remainingFormatted}",
                            dueDate: $dueDate,
                            notifiable: $ownerNotifiable,
                            recipientName: $compName,
                            recipientPhone: $company?->phone,
                            companyName: $compName
                        ));
                    }

                    // 4.4 Approaching Correction / Objection / Grace Stage Deadline
                    $stageDeadline = match ($violation->stage) {
                        ViolationStage::CORRECTION => $violation->correction_deadline ? Carbon::parse($violation->correction_deadline) : null,
                        ViolationStage::OBJECTION  => $violation->objection_deadline ? Carbon::parse($violation->objection_deadline) : null,
                        ViolationStage::GRACE      => $violation->grace_deadline ? Carbon::parse($violation->grace_deadline) : null,
                        default                    => null,
                    };

                    if ($stageDeadline && $stageDeadline->lessThanOrEqualTo($horizonDate)) {
                        $alerts->push($this->formatAlert(
                            id: 'violation_stage_' . $violation->id,
                            category: 'violations',
                            type: 'stage_deadline',
                            typeLabel: $violation->stage->getLabel(),
                            title: __('violations.notifications.stage_expiring_title') . " — {$vNumber}",
                            subtitle: "{$compName} | {$violation->issuing_authority} | " . __('violations.violations.fields.remaining_amount') . ": {$remainingFormatted}",
                            dueDate: $stageDeadline,
                            notifiable: $ownerNotifiable,
                            recipientName: $compName,
                            recipientPhone: $company?->phone,
                            companyName: $compName
                        ));
                    }
                }
            }

            // 5. Active Service Transactions Alerts
            if (class_exists(\App\Models\ServiceTransaction::class)) {
                $activeTransactions = \App\Models\ServiceTransaction::with(['company', 'employee.user', 'service'])
                    ->where('service_status', 'active')
                    ->whereNotNull('due_date')
                    ->get();

                foreach ($activeTransactions as $tx) {
                    $dueDate = Carbon::parse($tx->due_date);
                    if ($dueDate->lessThanOrEqualTo($horizonDate)) {
                        $title = $tx->display_service_name . " — {$tx->transaction_number}";
                        $subtitle = "{$tx->display_beneficiary} | " . __('services_tracking.fields.remaining_amount') . ": " . number_format($tx->remaining_amount, 2) . " SAR";
                        $alerts->push($this->formatAlert(
                            id: 'service_' . $tx->id,
                            category: 'tasks',
                            type: 'service_due',
                            typeLabel: __('services_tracking.single'),
                            title: $title,
                            subtitle: $subtitle,
                            dueDate: $dueDate,
                            notifiable: $tx->executor?->user ?? $tx->company?->owner?->user ?? null,
                            recipientName: $tx->display_beneficiary,
                            recipientPhone: $tx->beneficiary_phone ?? $tx->employee?->user?->phone,
                            companyName: $tx->company?->trade_name
                        ));
                    }
                }
            }
        } catch (\Throwable $e) {
            // Graceful fallback for missing database connection or empty tables
        }

        // Apply filters
        if ($category && $category !== 'all') {
            $alerts = $alerts->where('category', $category);
        }

        if ($urgency && $urgency !== 'all') {
            if ($urgency === 'urgent') {
                $alerts = $alerts->where('is_urgent', true);
            } elseif ($urgency === 'overdue') {
                $alerts = $alerts->where('days_remaining', '<', 0);
            } elseif ($urgency === 'upcoming') {
                $alerts = $alerts->where('days_remaining', '>=', 0);
            }
        }

        if ($search) {
            $searchLower = mb_strtolower($search);
            $alerts = $alerts->filter(function ($alert) use ($searchLower) {
                return str_contains(mb_strtolower($alert['title']), $searchLower)
                    || str_contains(mb_strtolower($alert['type_label']), $searchLower)
                    || str_contains(mb_strtolower($alert['subtitle'] ?? ''), $searchLower);
            });
        }

        // Sort by urgency: overdue and closest first
        return $alerts->sortBy('days_remaining')->values();
    }

    /**
     * Get summary counts for header widgets and badges.
     */
    public function getSummaryCounts(): array
    {
        $all = $this->getActiveAlerts('all');
        $urgent = $all->where('is_urgent', true)->count();
        $overdue = $all->where('days_remaining', '<', 0)->count();
        $documents = $all->where('category', 'documents')->count();
        $tasks = $all->where('category', 'tasks')->count();
        $invoices = $all->where('category', 'invoices')->count();
        $violations = $all->where('category', 'violations')->count();

        return [
            'total' => $all->count(),
            'urgent' => $urgent,
            'overdue' => $overdue,
            'documents' => $documents,
            'tasks' => $tasks,
            'invoices' => $invoices,
            'violations' => $violations,
        ];
    }

    /**
     * Helper to format a single alert structure.
     */
    protected function formatAlert(
        string $id,
        string $category,
        string $type,
        string $typeLabel,
        string $title,
        string $subtitle,
        Carbon $dueDate,
        mixed $notifiable,
        string $recipientName,
        ?string $recipientPhone = null,
        ?string $companyName = null
    ): array {
        $daysRemaining = (int) Carbon::today()->diffInDays($dueDate, false);
        $isUrgent = $daysRemaining <= 15;

        $urgencyLevel = match (true) {
            $daysRemaining < 0   => 'overdue',
            $daysRemaining <= 7  => 'critical',
            $daysRemaining <= 30 => 'warning',
            default              => 'normal',
        };

        $badgeColor = match ($urgencyLevel) {
            'overdue', 'critical' => 'danger',
            'warning'             => 'warning',
            default               => 'info',
        };

        $statusText = match (true) {
            $daysRemaining < 0  => __('notifications.stages.days_overdue', ['days' => abs($daysRemaining)]),
            $daysRemaining == 0 => __('notifications.stages.expires_today'),
            default             => __('notifications.stages.days_remaining', ['days' => $daysRemaining]),
        };

        return [
            'id' => $id,
            'category' => $category,
            'type' => $type,
            'type_label' => $typeLabel,
            'title' => $title,
            'subtitle' => $subtitle,
            'due_date' => $dueDate->format('Y-m-d'),
            'days_remaining' => $daysRemaining,
            'is_urgent' => $isUrgent,
            'urgency_level' => $urgencyLevel,
            'badge_color' => $badgeColor,
            'status_text' => $statusText,
            'notifiable' => $notifiable,
            'recipient_name' => $recipientName,
            'recipient_phone' => $recipientPhone,
            'company_name' => $companyName,
        ];
    }
}
