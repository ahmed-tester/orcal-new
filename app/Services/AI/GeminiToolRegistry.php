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
        // 1. الموظفون السعوديون (فاليديشن صارم للهوية الوطنية والجنسية)
        $this->register(new CreateSaudiEmployeeTool());

        // 2. الموظفون المقيمون / غير السعوديين (فاليديشن صارم للإقامة والجنسية غير السعودية)
        $this->register(new CreateForeignEmployeeTool());

        // 3. العملاء والجهات المستفيدة
        $this->register(new CreateCustomerTool());

        // 4. المؤسسات والشركات
        $this->register(new CreateCompanyTool());

        // 5. الإشراف والمناوبة
        $this->register(new CreateSupervisionShiftTool());

        // 6. حركات وسندات الخزينة (قبض / صرف)
        $this->register(new CreateTreasuryTransactionTool());

        // 7. بنود الدفع والتحصيل
        $this->register(new CreatePaymentCollectionItemTool());

        // 8. نقاط البيع والشبكات (POS)
        $this->register(new CreatePosDeviceTool());

        // 9. المخالفات والجزاءات
        $this->register(new CreateViolationTool());

        // 10. صندوق تحصيل المخالفات
        $this->register(new CreateViolationTransactionTool());

        // 11. أقسام وتصنيفات المخالفات
        $this->register(new CreateViolationCategoryTool());

        // 12. أنواع ومسميات المخالفات
        $this->register(new CreateViolationTypeTool());

        // 13. لوائح وأنظمة المخالفات
        $this->register(new CreateViolationRegulationTool());

        // 14. الفواتير وسندات الخدمات
        $this->register(new CreateInvoiceTool());

        // 15. أنواع الفواتير
        $this->register(new CreateInvoiceTypeTool());

        // 16. قائمة أسعار الخدمات
        $this->register(new CreateServiceTool());

        // 17. الأقساط والسلف
        $this->register(new CreateInstallmentTool());

        // 18. أنواع الأقساط
        $this->register(new CreateInstallmentTypeTool());

        // 19. مسيرات الرواتب (إعداد وتعديل)
        $this->register(new CreatePayrollRecordTool());

        // 20. صرف رواتب الموظفين (سندات صرف وخزينة)
        $this->register(new DisburseSalaryTool());

        // 21. سجلات الحضور والانصراف والبصمة
        $this->register(new CreateAttendanceTool());

        // 22. أجهزة البصمة والدوام
        $this->register(new CreateAttendanceDeviceTool());

        // 23. الإجازات والطلبات
        $this->register(new CreateLeaveTool());

        // 24. أنواع الإجازات
        $this->register(new CreateLeaveTypeTool());

        // 25. المكافآت والبدلات
        $this->register(new CreateEmployeeRewardTool());

        // 26. قيود اليومية (المبيعات والمشتريات)
        $this->register(new CreateDailyJournalTool());

        // 27. المهام والتكليفات
        $this->register(new CreateTaskTool());

        // 28. الفروع ومواقع العمل
        $this->register(new CreateBranchTool());

        // 29. الأقسام والإدارات
        $this->register(new CreateDepartmentTool());

        // 30. المسميات الوظيفية
        $this->register(new CreateJobTitleTool());

        // 31. صناديق القروض والسلف
        $this->register(new CreateLoanFundTool());

        // 32. مشتركو صناديق القروض والسلف
        $this->register(new CreateLoanFundMemberTool());

        // 33. قروض وسلف الصندوق
        $this->register(new CreateLoanFundLoanTool());

        // 34. سداد أقساط قروض الصندوق
        $this->register(new CreateLoanFundPaymentTool());

        // 35. الجمعيات المالية
        $this->register(new CreateRoscaFundTool());

        // 36. مشتركو الجمعيات المالية
        $this->register(new CreateRoscaFundMemberTool());

        // 37. دفعات وأقساط الجمعيات
        $this->register(new CreateRoscaPaymentTool());

        // 38. عروض الأسعار
        $this->register(new CreateOfferTool());

        // 39. الملاك والشركاء
        $this->register(new CreateOwnerTool());

        // 40. المستخدمون والمديرون
        $this->register(new CreateUserTool());

        // 41. تذاكر الدعم الفني
        $this->register(new CreateTicketTool());

        // 42. الأسئلة الشائعة
        $this->register(new CreateFaqTool());

        // 43. الملاحظات والتقارير الأسبوعية
        $this->register(new CreateWeeklyNoteTool());

        // 44. الديون والالتزامات المالية
        $this->register(new CreateDebtTool());

        // 45. سندات تحصيل وسداد الديون
        $this->register(new CreateDebtCollectionTool());
    }

    public function register(GeminiToolInterface $tool): self
    {
        $this->tools[$tool->getName()] = $tool;
        return $this;
    }

    /**
     * الحصول على جميع الأدوات المسجلة
     *
     * @return array<string, GeminiToolInterface>
     */
    public function getAllTools(): array
    {
        return $this->tools;
    }

    /**
     * الحصول على مصفوفة الـ Function Declarations لإرسالها لـ Gemini
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
     * تنفيذ الميثود الحقيقية محلياً عند تلقي طلب من Gemini
     */
    public function executeTool(string $toolName, array $arguments): array
    {
        if (!isset($this->tools[$toolName])) {
            return [
                'success' => false,
                'message' => "الدالة المطلوبة ({$toolName}) غير مسجلة في النظام.",
            ];
        }

        return $this->tools[$toolName]->execute($arguments);
    }
}
