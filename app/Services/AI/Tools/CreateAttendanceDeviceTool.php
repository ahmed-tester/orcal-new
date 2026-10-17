<?php

namespace App\Services\AI\Tools;

use App\Models\Branch;
use App\Models\Company;
use App\Models\AttendanceDevice;
use App\Services\AI\Contracts\GeminiToolInterface;

class CreateAttendanceDeviceTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_attendance_device';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'إدراج جهاز بصمة أو دوام جديد في النظام مع عنوان الـ IP والفرع.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'name' => [
                        'type' => 'STRING',
                        'description' => 'اسم الجهاز (مثال: بصمة البوابة الرئيسية، بصمة الفرع الأول)'
                    ],
                    'ip_address' => [
                        'type' => 'STRING',
                        'description' => 'عنوان IP للجهاز (مثال: 192.168.1.201)'
                    ],
                    'serial_number' => [
                        'type' => 'STRING',
                        'description' => 'الرقم التسلسلي للجهاز (Serial Number)'
                    ],
                    'model' => [
                        'type' => 'STRING',
                        'description' => 'موديل الجهاز (مثال: ZKTeco uFace 800، ZKTeco K40)'
                    ],
                    'branch_name' => [
                        'type' => 'STRING',
                        'description' => 'اسم الفرع التابع له الجهاز'
                    ],
                    'location' => [
                        'type' => 'STRING',
                        'description' => 'موقع الجهاز داخل المنشأة'
                    ]
                ],
                'required' => ['name']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        try {
            $name = trim($arguments['name'] ?? '');
            if (empty($name)) {
                return ['success' => false, 'message' => 'اسم جهاز البصمة مطلوب.'];
            }

            $companyId = auth()->user()?->company_id ?? Company::first()?->id ?? 1;

            $branchId = null;
            if (!empty($arguments['branch_name'])) {
                $bName = trim($arguments['branch_name']);
                $branch = Branch::where('company_id', $companyId)->where('name', 'like', "%{$bName}%")->first();
                $branchId = $branch?->id;
            }

            $duplicate = AttendanceDevice::where('company_id', $companyId)
                ->where('name', $name)
                ->first();

            if ($duplicate) {
                return [
                    'success' => false,
                    'message' => "تنبيه (مكرر): جهاز البصمة ({$name}) مسجل مسبقاً، تم تخطيه لمنع التكرار.",
                ];
            }

            $device = AttendanceDevice::create([
                'company_id'    => $companyId,
                'branch_id'     => $branchId,
                'name'          => $name,
                'ip_address'    => $arguments['ip_address'] ?? null,
                'serial_number' => $arguments['serial_number'] ?? null,
                'model'         => $arguments['model'] ?? null,
                'location'      => $arguments['location'] ?? null,
                'status'        => 'active',
            ]);

            return [
                'success'   => true,
                'message'   => "تم تسجيل جهاز البصمة ({$name}) بنجاح.",
                'device_id' => $device->id,
                'name'      => $name,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'تعذر حفظ جهاز البصمة: ' . $e->getMessage(),
            ];
        }
    }
}
