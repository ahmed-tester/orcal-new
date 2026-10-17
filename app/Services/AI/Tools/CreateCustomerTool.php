<?php

namespace App\Services\AI\Tools;

use App\Enums\GeneralStatus;
use App\Models\Customer;
use App\Services\AI\Contracts\GeminiToolInterface;

class CreateCustomerTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_customer_record';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'إضافة عميل أو جهة مستفيدة جديدة إلى النظام مع منع التكرار.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'name' => [
                        'type' => 'STRING',
                        'description' => 'اسم العميل أو اسم المؤسسة'
                    ],
                    'phone' => [
                        'type' => 'STRING',
                        'description' => 'رقم الهاتف أو الجوال'
                    ],
                    'national_id' => [
                        'type' => 'STRING',
                        'description' => 'رقم الهوية الوطنية أو الإقامة'
                    ],
                    'tax_number' => [
                        'type' => 'STRING',
                        'description' => 'الرقم الضريبي (اختياري)'
                    ],
                    'commercial_registration_number' => [
                        'type' => 'STRING',
                        'description' => 'رقم السجل التجاري (اختياري)'
                    ],
                    'email' => [
                        'type' => 'STRING',
                        'description' => 'البريد الإلكتروني (اختياري)'
                    ],
                    'city' => [
                        'type' => 'STRING',
                        'description' => 'المدينة (اختياري)'
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
            $phone = trim($arguments['phone'] ?? '');
            $nationalId = trim($arguments['national_id'] ?? '');
            $taxNumber = trim($arguments['tax_number'] ?? '');
            $crNumber = trim($arguments['commercial_registration_number'] ?? '');

            if (empty($name)) {
                return ['success' => false, 'message' => 'اسم العميل مطلوب.'];
            }

            // 1. فحص منع التكرار بالرقم الضريبي
            if (!empty($taxNumber) && Customer::where('tax_number', $taxNumber)->exists()) {
                return [
                    'success' => false,
                    'message' => "تنبيه (مكرر): العميل بالرقم الضريبي ({$taxNumber}) مسجل مسبقاً في النظام.",
                ];
            }

            // 2. فحص منع التكرار برقم السجل التجاري
            if (!empty($crNumber) && Customer::where('commercial_registration_number', $crNumber)->exists()) {
                return [
                    'success' => false,
                    'message' => "تنبيه (مكرر): العميل برقم السجل ({$crNumber}) مسجل مسبقاً في النظام.",
                ];
            }

            // 3. فحص منع التكرار برقم الهوية
            if (!empty($nationalId) && Customer::where('national_id', $nationalId)->exists()) {
                return [
                    'success' => false,
                    'message' => "تنبيه (مكرر): العميل برقم الهوية ({$nationalId}) مسجل مسبقاً في النظام.",
                ];
            }

            // 4. فحص منع التكرار بالاسم ورقم الهاتف
            $duplicateQuery = Customer::where('name', $name);
            if (!empty($phone)) {
                $duplicateQuery->where('phone', $phone);
            }
            if ($duplicateQuery->exists()) {
                return [
                    'success' => false,
                    'message' => "تنبيه (مكرر): العميل ({$name}) مسجل مسبقاً في النظام ولم تتم إضافته ثانية.",
                ];
            }

            $customer = Customer::create([
                'name' => $name,
                'phone' => $phone ?: null,
                'national_id' => $nationalId ?: null,
                'tax_number' => $taxNumber ?: null,
                'commercial_registration_number' => $crNumber ?: null,
                'email' => $arguments['email'] ?? null,
                'city' => $arguments['city'] ?? null,
                'status' => GeneralStatus::ACTIVE,
            ]);

            return [
                'success' => true,
                'message' => "تم إضافة العميل ({$name}) بنجاح برقم #{$customer->id}",
                'customer_id' => $customer->id,
                'name' => $name,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'تعذر حفظ العميل: ' . $e->getMessage(),
            ];
        }
    }
}
