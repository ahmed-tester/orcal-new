<?php

namespace App\Services\AI\Tools;

use App\Models\Company;
use App\Models\Employee;
use App\Models\SupervisionShift;
use App\Services\AI\Contracts\GeminiToolInterface;

class CreateSupervisionShiftTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_supervision_shift';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'إدراج وردية أو مناوبة إشرافية جديدة لفرق العمل مع تحديد المشرف والمواعيد والمهام.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'title' => [
                        'type' => 'STRING',
                        'description' => 'عنوان أو مسمى وردية الإشراف / المناوبة'
                    ],
                    'supervisor_identifier' => [
                        'type' => 'STRING',
                        'description' => 'اسم المشرف المسؤول أو رقمه الوظيفي أو هويته'
                    ],
                    'start_date' => [
                        'type' => 'STRING',
                        'description' => 'تاريخ البداية بصيغة YYYY-MM-DD'
                    ],
                    'end_date' => [
                        'type' => 'STRING',
                        'description' => 'تاريخ النهاية بصيغة YYYY-MM-DD'
                    ],
                    'start_time' => [
                        'type' => 'STRING',
                        'description' => 'وقت بداية المناوبة (مثال: 08:00)'
                    ],
                    'end_time' => [
                        'type' => 'STRING',
                        'description' => 'وقت نهاية المناوبة (مثال: 16:00)'
                    ],
                    'tasks' => [
                        'type' => 'STRING',
                        'description' => 'المهام والتكليفات المطلوبة خلال الوردية'
                    ],
                    'notes' => [
                        'type' => 'STRING',
                        'description' => 'ملاحظات إضافية'
                    ]
                ],
                'required' => ['title']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        try {
            $title = trim($arguments['title'] ?? '');
            if (empty($title)) {
                return ['success' => false, 'message' => 'عنوان وردية الإشراف مطلوب.'];
            }

            $companyId = auth()->user()?->company_id ?? Company::first()?->id ?? 1;

            $supervisorId = null;
            if (!empty($arguments['supervisor_identifier'])) {
                $ident = trim($arguments['supervisor_identifier']);
                $emp = Employee::where('company_id', $companyId)
                    ->where(function ($q) use ($ident) {
                        $q->where('id', $ident)
                          ->orWhere('employee_number', $ident)
                          ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$ident}%")->orWhere('phone', 'like', "%{$ident}%"));
                    })->first();
                $supervisorId = $emp?->id;
            }

            $startDate = !empty($arguments['start_date']) ? $arguments['start_date'] : date('Y-m-d');
            $endDate = !empty($arguments['end_date']) ? $arguments['end_date'] : $startDate;

            $shift = SupervisionShift::create([
                'company_id'    => $companyId,
                'supervisor_id' => $supervisorId,
                'title'         => $title,
                'start_date'    => $startDate,
                'end_date'      => $endDate,
                'start_time'    => $arguments['start_time'] ?? null,
                'end_time'      => $arguments['end_time'] ?? null,
                'tasks'         => $arguments['tasks'] ?? null,
                'notes'         => $arguments['notes'] ?? null,
                'status'        => 'active',
                'type'          => 'supervision',
                'created_by'    => auth()->id(),
            ]);

            return [
                'success' => true,
                'message' => "تم إنشاء وردية الإشراف والمناوبة ({$title}) بنجاح.",
                'shift_id' => $shift->id,
                'name' => $title,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'تعذر حفظ وردية الإشراف: ' . $e->getMessage(),
            ];
        }
    }
}
