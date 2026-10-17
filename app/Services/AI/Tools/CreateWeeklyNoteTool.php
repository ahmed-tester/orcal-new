<?php

namespace App\Services\AI\Tools;

use App\Enums\WeeklyNoteStatus;
use App\Models\Company;
use App\Models\Employee;
use App\Models\WeeklyNote;
use App\Services\AI\Contracts\GeminiToolInterface;

class CreateWeeklyNoteTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_weekly_note_record';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'إدراج مذكرة أسبوعية أو تقرير متابعة دوري مع إمكانية إسناده لموظف معين.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'title' => [
                        'type' => 'STRING',
                        'description' => 'عنوان المذكرة أو التقرير الأسبوعي'
                    ],
                    'content' => [
                        'type' => 'STRING',
                        'description' => 'نص وتفاصيل المذكرة'
                    ],
                    'note_date' => [
                        'type' => 'STRING',
                        'description' => 'تاريخ المذكرة بصيغة YYYY-MM-DD'
                    ],
                    'assigned_employee' => [
                        'type' => 'STRING',
                        'description' => 'اسم الموظف المسند إليه المذكرة (اختياري)'
                    ]
                ],
                'required' => ['title', 'content']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        try {
            $title = trim($arguments['title'] ?? '');
            $content = trim($arguments['content'] ?? '');
            $date = trim($arguments['note_date'] ?? date('Y-m-d'));
            $empName = trim($arguments['assigned_employee'] ?? '');

            if (empty($title) || empty($content)) {
                return ['success' => false, 'message' => 'عنوان ومحتوى المذكرة مطلوبان.'];
            }

            $companyId = auth()->user()?->company_id ?? Company::first()?->id ?? 1;

            $assignedId = null;
            if (!empty($empName)) {
                $employee = Employee::where('company_id', $companyId)
                    ->whereHas('user', fn($q) => $q->where('name', 'like', "%{$empName}%"))
                    ->first();
                $assignedId = $employee?->id;
            }

            $existing = WeeklyNote::where('company_id', $companyId)
                ->where('title', $title)
                ->whereDate('note_date', $date)
                ->first();

            if ($existing) {
                return [
                    'success' => false,
                    'message' => "تنبيه (مكرر): المذكرة ({$title}) بتاريخ ({$date}) مسجلة مسبقاً، تم تخطيها.",
                ];
            }

            $note = WeeklyNote::create([
                'company_id' => $companyId,
                'title' => $title,
                'content' => $content,
                'note_date' => $date,
                'status' => WeeklyNoteStatus::NEW,
                'assigned_to' => $assignedId,
                'created_by' => auth()->id(),
            ]);

            return [
                'success' => true,
                'message' => "تم تسجيل المذكرة ({$title}) بنجاح برقم #{$note->voucher_number}",
                'record_name' => $title,
                'details' => [
                    'title' => $title,
                    'voucher' => $note->voucher_number,
                    'date' => $date,
                ]
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ المذكرة: ' . $e->getMessage(),
            ];
        }
    }
}
