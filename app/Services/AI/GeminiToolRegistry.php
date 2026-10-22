<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\GeminiToolInterface;
use App\Services\AI\Tools\CreateAttendanceDeviceTool;
use App\Services\AI\Tools\CreateAttendanceTool;
use App\Services\AI\Tools\CreateBranchTool;
use App\Services\AI\Tools\CreateCompanyTool;
use App\Services\AI\Tools\CreateCustomerTool;
use App\Services\AI\Tools\CreateDailyJournalTool;
use App\Services\AI\Tools\CreateDebtCollectionTool;
use App\Services\AI\Tools\CreateDebtTool;
use App\Services\AI\Tools\CreateDepartmentTool;
use App\Services\AI\Tools\CreateEmployeeRewardTool;
use App\Services\AI\Tools\CreateFaqTool;
use App\Services\AI\Tools\CreateForeignEmployeeTool;
use App\Services\AI\Tools\CreateInstallmentPaymentTool;
use App\Services\AI\Tools\CreateInstallmentTool;
use App\Services\AI\Tools\CreateInstallmentTypeTool;
use App\Services\AI\Tools\CreateInvoiceTool;
use App\Services\AI\Tools\CreateInvoiceTypeTool;
use App\Services\AI\Tools\CreateJobTitleTool;
use App\Services\AI\Tools\CreateLeaveTool;
use App\Services\AI\Tools\CreateLeaveTypeTool;
use App\Services\AI\Tools\CreateLoanFundLoanTool;
use App\Services\AI\Tools\CreateLoanFundMemberTool;
use App\Services\AI\Tools\CreateLoanFundPaymentTool;
use App\Services\AI\Tools\CreateLoanFundTool;
use App\Services\AI\Tools\CreateOfferTool;
use App\Services\AI\Tools\CreateOwnerTool;
use App\Services\AI\Tools\CreatePaymentCollectionItemTool;
use App\Services\AI\Tools\CreatePayrollRecordTool;
use App\Services\AI\Tools\CreatePosDeviceTool;
use App\Services\AI\Tools\DisburseSalaryTool;
use App\Services\AI\Tools\CreateRoscaFundMemberTool;
use App\Services\AI\Tools\CreateRoscaFundTool;
use App\Services\AI\Tools\CreateRoscaPaymentTool;
use App\Services\AI\Tools\CreateSaudiEmployeeTool;
use App\Services\AI\Tools\CreateServiceTool;
use App\Services\AI\Tools\CreateSupervisionShiftTool;
use App\Services\AI\Tools\CreateTaskTool;
use App\Services\AI\Tools\CreateTicketTool;
use App\Services\AI\Tools\CreateTreasuryTransactionTool;
use App\Services\AI\Tools\CreateUserTool;
use App\Services\AI\Tools\CreateViolationCategoryTool;
use App\Services\AI\Tools\CreateViolationRegulationTool;
use App\Services\AI\Tools\CreateViolationTool;
use App\Services\AI\Tools\CreateViolationTransactionTool;
use App\Services\AI\Tools\CreateViolationTypeTool;
use App\Services\AI\Tools\CreateWeeklyNoteTool;

class GeminiToolRegistry
{
    /**
     * @var array<string, GeminiToolInterface>
     */
    protected array $tools = [];

    public function __construct()
    {
        // 1. Saudi Employees
        $this->register(new CreateSaudiEmployeeTool());

        // 2. Foreign / Resident Employees
        $this->register(new CreateForeignEmployeeTool());

        // 3. Customers and Beneficiaries
        $this->register(new CreateCustomerTool());

        // 4. Companies and Establishments
        $this->register(new CreateCompanyTool());

        // 5. Supervision and Shifts
        $this->register(new CreateSupervisionShiftTool());

        // 6. Treasury Transactions
        $this->register(new CreateTreasuryTransactionTool());

        // 7. Payment Collection Items
        $this->register(new CreatePaymentCollectionItemTool());

        // 8. POS Devices
        $this->register(new CreatePosDeviceTool());

        // 9. Violations and Penalties
        $this->register(new CreateViolationTool());

        // 10. Violation Transactions
        $this->register(new CreateViolationTransactionTool());

        // 11. Violation Categories
        $this->register(new CreateViolationCategoryTool());

        // 12. Violation Types
        $this->register(new CreateViolationTypeTool());

        // 13. Violation Regulations
        $this->register(new CreateViolationRegulationTool());

        // 14. Invoices
        $this->register(new CreateInvoiceTool());

        // 15. Invoice Types
        $this->register(new CreateInvoiceTypeTool());

        // 16. Service Price List
        $this->register(new CreateServiceTool());

        // 17. Installments
        $this->register(new CreateInstallmentTool());
        $this->register(new CreateInstallmentPaymentTool());

        // 18. Installment Types
        $this->register(new CreateInstallmentTypeTool());

        // 19. Payroll Records
        $this->register(new CreatePayrollRecordTool());

        // 20. Salary Disbursements
        $this->register(new DisburseSalaryTool());

        // 21. Attendance Records
        $this->register(new CreateAttendanceTool());

        // 22. Attendance Devices
        $this->register(new CreateAttendanceDeviceTool());

        // 23. Leave Requests
        $this->register(new CreateLeaveTool());

        // 24. Leave Types
        $this->register(new CreateLeaveTypeTool());

        // 25. Employee Rewards
        $this->register(new CreateEmployeeRewardTool());

        // 26. Daily Journals
        $this->register(new CreateDailyJournalTool());

        // 27. Tasks
        $this->register(new CreateTaskTool());

        // 28. Branches
        $this->register(new CreateBranchTool());

        // 29. Departments
        $this->register(new CreateDepartmentTool());

        // 30. Job Titles
        $this->register(new CreateJobTitleTool());

        // 31. Loan Funds
        $this->register(new CreateLoanFundTool());

        // 32. Loan Fund Members
        $this->register(new CreateLoanFundMemberTool());

        // 33. Loan Fund Loans
        $this->register(new CreateLoanFundLoanTool());

        // 34. Loan Fund Payments
        $this->register(new CreateLoanFundPaymentTool());

        // 35. ROSCA Funds
        $this->register(new CreateRoscaFundTool());

        // 36. ROSCA Fund Members
        $this->register(new CreateRoscaFundMemberTool());

        // 37. ROSCA Payments
        $this->register(new CreateRoscaPaymentTool());

        // 38. Price Offers
        $this->register(new CreateOfferTool());

        // 39. Owners and Partners
        $this->register(new CreateOwnerTool());

        // 40. System Users
        $this->register(new CreateUserTool());

        // 41. Support Tickets
        $this->register(new CreateTicketTool());

        // 42. FAQs
        $this->register(new CreateFaqTool());

        // 43. Weekly Notes
        $this->register(new CreateWeeklyNoteTool());

        // 44. Financial Debts
        $this->register(new CreateDebtTool());

        // 45. Debt Collections
        $this->register(new CreateDebtCollectionTool());
    }

    public function register(GeminiToolInterface $tool): self
    {
        $this->tools[$tool->getName()] = $tool;
        return $this;
    }

    /**
     * Get all registered tools.
     *
     * @return array<string, GeminiToolInterface>
     */
    public function getAllTools(): array
    {
        return $this->tools;
    }

    /**
     * Get function declarations payload for Gemini API.
     */
    public function getDeclarationsForGemini(?array $allowedToolNames = null): array
    {
        $declarations = [];

        foreach ($this->tools as $name => $tool) {
            if ($allowedToolNames !== null && !in_array($name, $allowedToolNames, true)) {
                continue;
            }
            $declarations[] = $tool->getDeclaration();
        }

        return [
            [
                'function_declarations' => $declarations,
            ]
        ];
    }

    /**
     * Execute a specific tool by name.
     */
    public function executeTool(string $toolName, array $arguments): array
    {
        if (!isset($this->tools[$toolName])) {
            return [
                'success' => false,
                'message' => __('smart_import.service.tool_not_registered', ['tool' => $toolName]),
            ];
        }

        return $this->tools[$toolName]->execute($arguments);
    }
}
