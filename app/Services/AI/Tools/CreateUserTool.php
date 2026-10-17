<?php

namespace App\Services\AI\Tools;

use App\Enums\UserType;
use App\Models\User;
use App\Services\AI\Contracts\GeminiToolInterface;
use Illuminate\Support\Facades\Hash;

class CreateUserTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_user_record';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'إدراج مستخدم أو مدير جديد للنظام مع فحص البريد الإلكتروني ورقم الجوال لمنع التكرار.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'name' => [
                        'type' => 'STRING',
                        'description' => 'اسم المستخدم الكامل'
                    ],
                    'email' => [
                        'type' => 'STRING',
                        'description' => 'البريد الإلكتروني'
                    ],
                    'phone' => [
                        'type' => 'STRING',
                        'description' => 'رقم الجوال'
                    ],
                    'user_type' => [
                        'type' => 'STRING',
                        'description' => 'نوع المستخدم: admin, supervisor, owner (الافتراضي supervisor)'
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
                return ['success' => false, 'message' => 'اسم المستخدم مطلوب.'];
            }

            $email = !empty($arguments['email']) ? trim($arguments['email']) : null;
            $phone = !empty($arguments['phone']) ? trim($arguments['phone']) : null;

            if ($email) {
                $duplicate = User::where('email', $email)->first();
                if ($duplicate) {
                    return [
                        'success' => false,
                        'message' => "تنبيه (مكرر): المستخدم صاحب البريد ({$email}) مسجل مسبقاً، تم تخطيه لمنع التكرار.",
                    ];
                }
            }

            if ($phone) {
                $duplicatePhone = User::where('phone', $phone)->first();
                if ($duplicatePhone) {
                    return [
                        'success' => false,
                        'message' => "تنبيه (مكرر): المستخدم صاحب الجوال ({$phone}) مسجل مسبقاً، تم تخطيه لمنع التكرار.",
                    ];
                }
            }

            $typeInput = strtolower(trim($arguments['user_type'] ?? 'supervisor'));
            $userType = UserType::tryFrom($typeInput) ?? UserType::SUPERVISOR;

            $generatedEmail = $email ?: 'user_' . time() . '_' . rand(100, 999) . '@system.local';

            $user = User::create([
                'name'      => $name,
                'email'     => $generatedEmail,
                'phone'     => $phone,
                'password'  => Hash::make('password123'),
                'user_type' => $userType,
                'status'    => 'active',
            ]);

            return [
                'success' => true,
                'message' => "تم إنشاء حساب المستخدم ({$name}) بنجاح (كلمة المرور الافتراضية: password123).",
                'user_id' => $user->id,
                'name'    => $name,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'تعذر حفظ المستخدم: ' . $e->getMessage(),
            ];
        }
    }
}
