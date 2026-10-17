<?php

namespace App\Services\AI\Tools;

use App\Enums\GeneralStatus;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\Owner;
use App\Models\User;
use App\Services\AI\Contracts\GeminiToolInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateOwnerTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_owner_record';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'إدراج مالك أو شريك مؤسس للمنشأة مع تحديد نسبة الملكية ورقم الهوية.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'name' => [
                        'type' => 'STRING',
                        'description' => 'اسم المالك أو الشريك'
                    ],
                    'national_id' => [
                        'type' => 'STRING',
                        'description' => 'رقم الهوية الوطنية أو السجل'
                    ],
                    'phone' => [
                        'type' => 'STRING',
                        'description' => 'رقم الجوال'
                    ],
                    'ownership_percentage' => [
                        'type' => 'NUMBER',
                        'description' => 'نسبة الملكية أو الشراكة كنسبة مئوية (مثال: 50 أو 25.5)'
                    ],
                    'nationality' => [
                        'type' => 'STRING',
                        'description' => 'الجنسية (الافتراضي: سعودي)'
                    ]
                ],
                'required' => ['name', 'national_id']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        try {
            $name = trim($arguments['name'] ?? '');
            $nationalId = trim($arguments['national_id'] ?? '');
            $phone = trim($arguments['phone'] ?? '');
            $percentage = (float)($arguments['ownership_percentage'] ?? 0);
            $nationality = trim($arguments['nationality'] ?? 'سعودي');

            if (empty($name) || empty($nationalId)) {
                return ['success' => false, 'message' => 'اسم المالك ورقم الهوية مطلوبان.'];
            }

            $companyId = auth()->user()?->company_id ?? Company::first()?->id ?? 1;

            // فحص منع التكرار
            $existing = Owner::where('company_id', $companyId)
                ->where('national_id', $nationalId)
                ->first();

            if ($existing) {
                return [
                    'success' => false,
                    'message' => "تنبيه (مكرر): المالك ({$name}) بهوية ({$nationalId}) مسجل مسبقاً، تم تخطيه.",
                ];
            }

            // إنشاء حساب مستخدم للمالك
            $email = 'owner_' . $nationalId . '@' . (Company::find($companyId)?->name ? Str::slug(Company::find($companyId)->name) : 'company') . '.local';
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'phone' => $phone ?: null,
                    'password' => Hash::make(Str::random(16)),
                    'user_type' => UserType::OWNER,
                    'company_id' => $companyId,
                ]
            );

            Owner::create([
                'user_id' => $user->id,
                'company_id' => $companyId,
                'nationality' => $nationality,
                'national_id' => $nationalId,
                'ownership_percentage' => $percentage,
                'employment_date' => date('Y-m-d'),
                'status' => GeneralStatus::ACTIVE,
            ]);

            return [
                'success' => true,
                'message' => "تم تسجيل المالك/الشريك ({$name}) بنجاح بنسبة ملكية " . $percentage . "%",
                'record_name' => $name,
                'details' => [
                    'name' => $name,
                    'national_id' => $nationalId,
                    'percentage' => $percentage . '%',
                ]
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ المالك: ' . $e->getMessage(),
            ];
        }
    }
}
