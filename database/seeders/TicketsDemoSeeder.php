<?php

namespace Database\Seeders;

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Database\Seeder;

class TicketsDemoSeeder extends Seeder
{
    /**
     * Seed realistic demo tickets across statuses, priorities, and categories.
     *
     * @return array<int, array{id: int, label: string}>
     */
    public function run(): array
    {
        $admin = User::query()
            ->where('user_type', UserType::SUPER_ADMIN)
            ->first()
            ?? User::query()->first();

        if (! $admin) {
            return [];
        }

        $companies = Company::query()->take(2)->get();

        if ($companies->isEmpty()) {
            $companies = collect([
                Company::firstOrCreate(
                    ['code' => 'PERF-DEMO-A'],
                    ['trade_name' => 'شركة الأداء التجريبية أ', 'status' => 'active']
                ),
            ]);
        }

        $samples = [
            ['TCK-DEMO-0001', TicketStatus::OPEN, TicketPriority::URGENT, TicketCategory::TECHNICAL, 'تعطل مزامنة جهاز البصمة في الفرع الرئيسي'],
            ['TCK-DEMO-0002', TicketStatus::OPEN, TicketPriority::HIGH, TicketCategory::FINANCE, 'مراجعة فروقات عهدة سداد الرسوم الحكومية'],
            ['TCK-DEMO-0003', TicketStatus::IN_PROGRESS, TicketPriority::URGENT, TicketCategory::COMPLAINT, 'تأخر تجديد رخصة العمل لأحد الموظفين'],
            ['TCK-DEMO-0004', TicketStatus::IN_PROGRESS, TicketPriority::MEDIUM, TicketCategory::HR, 'تحديث بيانات الإقامة وتعديل المسمى الوظيفي'],
            ['TCK-DEMO-0005', TicketStatus::WAITING_USER_REPLY, TicketPriority::HIGH, TicketCategory::TECHNICAL, 'طلب صلاحية الوصول لتقارير الرواتب الشهرية'],
            ['TCK-DEMO-0006', TicketStatus::WAITING_USER_REPLY, TicketPriority::LOW, TicketCategory::GENERAL, 'استفسار عن آلية اعتماد طلبات الإجازات السنوية'],
            ['TCK-DEMO-0007', TicketStatus::RESOLVED, TicketPriority::MEDIUM, TicketCategory::TECHNICAL, 'إصلاح مشكلة تصدير كشف الحضور بصيغة PDF'],
            ['TCK-DEMO-0008', TicketStatus::RESOLVED, TicketPriority::HIGH, TicketCategory::FINANCE, 'اعتماد سند قبض متأخر لمعاملة خدمات'],
            ['TCK-DEMO-0009', TicketStatus::CLOSED, TicketPriority::LOW, TicketCategory::SUGGESTION, 'اقتراح إضافة تنبيه تلقائي قبل انتهاء التأمين الطبي'],
            ['TCK-DEMO-0010', TicketStatus::CLOSED, TicketPriority::MEDIUM, TicketCategory::HR, 'إصدار تعريف راتب لموظف سعودي'],
            ['TCK-DEMO-0011', TicketStatus::OPEN, TicketPriority::MEDIUM, TicketCategory::COMPLAINT, 'ملاحظة على بطء تحميل صفحة المستندات'],
            ['TCK-DEMO-0012', TicketStatus::IN_PROGRESS, TicketPriority::LOW, TicketCategory::SUGGESTION, 'توحيد قوالب الطباعة الرسمية للمراسلات'],
        ];

        $companyCount = $companies->count();
        $now = now();

        foreach ($samples as $index => [$number, $status, $priority, $category, $subject]) {
            $company = $companies[$index % $companyCount];
            $isResolved = $status === TicketStatus::RESOLVED;
            $isClosed = $status === TicketStatus::CLOSED;

            $ticket = Ticket::updateOrCreate(
                ['ticket_number' => $number],
                [
                    'user_id' => $admin->id,
                    'company_id' => $company->id,
                    'assigned_to' => $admin->id,
                    'category' => $category->value,
                    'priority' => $priority->value,
                    'status' => $status->value,
                    'subject' => $subject,
                    'description' => "تذكرة تجريبية لعرض مؤشرات الدعم الفني ({$subject}).",
                    'last_reply_at' => $now->copy()->subHours($index + 1),
                    'resolved_at' => $isResolved || $isClosed ? $now->copy()->subHours(2) : null,
                    'closed_at' => $isClosed ? $now->copy()->subHour() : null,
                ]
            );

            if ($ticket->messages()->count() === 0) {
                TicketMessage::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $admin->id,
                    'message' => 'تم استلام التذكرة وجارٍ المتابعة من فريق الدعم الفني.',
                    'is_internal_note' => false,
                ]);
            }
        }

        return $companies->map(fn (Company $company): array => [
            'id' => $company->id,
            'label' => $company->trade_name ?: __('performance.fallback.company', ['id' => $company->id]),
        ])->values()->all();
    }
}
