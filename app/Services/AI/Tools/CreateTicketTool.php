<?php

namespace App\Services\AI\Tools;

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Company;
use App\Models\Ticket;
use App\Services\AI\Contracts\GeminiToolInterface;

class CreateTicketTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_ticket_record';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'إدراج تذكرة دعم فني أو مساعدة جديدة في النظام وتحديد التصنيف والأولوية.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'subject' => [
                        'type' => 'STRING',
                        'description' => 'موضوع التذكرة أو المشكلة'
                    ],
                    'description' => [
                        'type' => 'STRING',
                        'description' => 'التفاصيل الكاملة للتذكرة أو الطلب'
                    ],
                    'category' => [
                        'type' => 'STRING',
                        'description' => 'التصنيف: technical (فني), hr (موارد بشرية), finance (مالي), complaint (شكوى), general (عام)'
                    ],
                    'priority' => [
                        'type' => 'STRING',
                        'description' => 'الأولوية: low, medium, high, urgent (الافتراضي medium)'
                    ]
                ],
                'required' => ['subject', 'description']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        try {
            $subject = trim($arguments['subject'] ?? '');
            $description = trim($arguments['description'] ?? '');

            if (empty($subject) || empty($description)) {
                return ['success' => false, 'message' => 'موضوع وتفاصيل التذكرة مطلوبان.'];
            }

            $companyId = auth()->user()?->company_id ?? Company::first()?->id ?? 1;
            $userId = auth()->id() ?? 1;

            $catInput = strtolower(trim($arguments['category'] ?? 'general'));
            $category = TicketCategory::tryFrom($catInput) ?? TicketCategory::GENERAL;

            $prioInput = strtolower(trim($arguments['priority'] ?? 'medium'));
            $priority = TicketPriority::tryFrom($prioInput) ?? TicketPriority::MEDIUM;

            $ticket = Ticket::create([
                'company_id'  => $companyId,
                'user_id'     => $userId,
                'subject'     => $subject,
                'description' => $description,
                'category'    => $category,
                'priority'    => $priority,
                'status'      => TicketStatus::OPEN,
            ]);

            return [
                'success'   => true,
                'message'   => "تم فتح تذكرة الدعم ({$subject}) بنجاح برقم #{$ticket->ticket_number}.",
                'ticket_id' => $ticket->id,
                'name'      => "تذكرة #{$ticket->ticket_number}",
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'تعذر حفظ تذكرة الدعم: ' . $e->getMessage(),
            ];
        }
    }
}
