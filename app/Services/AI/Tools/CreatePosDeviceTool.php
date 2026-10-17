<?php

namespace App\Services\AI\Tools;

use App\Enums\PosDeviceStatus;
use App\Models\Company;
use App\Models\PosDevice;
use App\Services\AI\Contracts\GeminiToolInterface;

class CreatePosDeviceTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_pos_device';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'إدراج جهاز نقاط بيع وشبكة (POS) جديد بالبيانات البنكية ورقم الجهاز.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'device_number' => [
                        'type' => 'STRING',
                        'description' => 'رقم جهاز نقاط البيع (Device / Terminal ID)'
                    ],
                    'bank_or_wallet_name' => [
                        'type' => 'STRING',
                        'description' => 'اسم البنك أو المحفظة المالية (مثال: الراجحي، الأهلي، الإنماء، مدى)'
                    ],
                    'card_number' => [
                        'type' => 'STRING',
                        'description' => 'رقم بطاقة الصراف أو شريحة الجهاز'
                    ],
                    'account_number' => [
                        'type' => 'STRING',
                        'description' => 'رقم الحساب أو الآيبان المرتبط بالجهاز'
                    ],
                    'commercial_registration_number' => [
                        'type' => 'STRING',
                        'description' => 'رقم السجل التجاري التابع له الجهاز'
                    ],
                    'notes' => [
                        'type' => 'STRING',
                        'description' => 'ملاحظات إضافية'
                    ]
                ],
                'required' => ['device_number']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        try {
            $deviceNumber = trim($arguments['device_number'] ?? '');
            if (empty($deviceNumber)) {
                return ['success' => false, 'message' => 'رقم جهاز نقاط البيع مطلوب.'];
            }

            $companyId = auth()->user()?->company_id ?? Company::first()?->id ?? 1;

            $duplicate = PosDevice::where('company_id', $companyId)
                ->where('device_number', $deviceNumber)
                ->first();

            if ($duplicate) {
                return [
                    'success' => false,
                    'message' => "تنبيه (مكرر): جهاز نقاط البيع برقم ({$deviceNumber}) مسجل مسبقاً، تم تخطيه لمنع التكرار.",
                ];
            }

            $device = PosDevice::create([
                'company_id'                     => $companyId,
                'device_number'                  => $deviceNumber,
                'bank_or_wallet_name'            => $arguments['bank_or_wallet_name'] ?? null,
                'card_number'                    => $arguments['card_number'] ?? null,
                'account_number'                 => $arguments['account_number'] ?? null,
                'commercial_registration_number' => $arguments['commercial_registration_number'] ?? null,
                'notes'                          => $arguments['notes'] ?? null,
                'status'                         => PosDeviceStatus::ACTIVE,
            ]);

            return [
                'success'   => true,
                'message'   => "تم تسجيل جهاز نقاط البيع ({$deviceNumber}) بنجاح.",
                'device_id' => $device->id,
                'name'      => $deviceNumber,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'تعذر حفظ جهاز نقاط البيع: ' . $e->getMessage(),
            ];
        }
    }
}
