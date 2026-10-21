<?php

namespace App\Console\Commands;

use App\Enums\InstallmentStatus;
use App\Events\DueDateReminderEvent;
use App\Models\Installment;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckInstallmentsDueCommand extends Command
{
    protected $signature = 'installments:check-due
                            {--id= : Check specific installment ID}
                            {--force : Force send alerts even if not yet due}';

    protected $description = 'Check installments due date and dispatch reminder notifications';

    public function handle(): int
    {
        $today = Carbon::today();
        $targetId = $this->option('id');
        $isForce = (bool) $this->option('force');

        $this->info('🔍 فحص استحقاقات الأقساط والسلف...');

        $query = Installment::with(['employee.user', 'notifyEmployee.user', 'company'])
            ->where('status', '!=', InstallmentStatus::COMPLETED)
            ->where('remaining_amount', '>', 0);

        if ($targetId) {
            $query->where('id', $targetId);
        } elseif (!$isForce) {
            $query->whereNotNull('start_due_date')
                  ->whereDate('start_due_date', '<=', $today);
        }

        $installments = $query->get();

        if ($installments->isEmpty()) {
            $this->warn('ℹ️ لا توجد أقساط مستحقة حالياً.');
            return Command::SUCCESS;
        }

        $sentCount = 0;

        foreach ($installments as $installment) {
            $empName = $installment->employee?->user?->name
                ?? $installment->external_name
                ?? ('معاملة #' . $installment->id);

            $code = $installment->installment_number ?? ('#' . $installment->id);
            $amount = number_format((float)($installment->monthly_amount > 0 ? $installment->monthly_amount : $installment->remaining_amount), 2);
            $dueDate = $installment->start_due_date 
                ? Carbon::parse($installment->start_due_date)->format('Y-m-d') 
                : $today->format('Y-m-d');

            // Determine recipient: notifyEmployee if assigned, otherwise null (permitted users will get it)
            $recipient = $installment->notifyEmployee?->user 
                ?? $installment->notifyEmployee;

            $customNotes = !empty($installment->notification_notes)
                ? trim($installment->notification_notes)
                : null;

            $this->line("🔔 إرسال تنبيه للمعاملة: [{$code}] - المستفيد: {$empName} - المبلغ: {$amount} ر.س");

            event(new DueDateReminderEvent(
                recipient: $recipient,
                customMessage: $customNotes,
                placeholders: [
                    'employee_name' => $empName,
                    'code'          => $code,
                    'amount'        => $amount,
                    'due_date'      => $dueDate,
                ]
            ));

            $sentCount++;
        }

        $this->info("✅ تم إرسال {$sentCount} تنبيه(ات) بنجاح.");
        Log::info("[Installments Due Command] Dispatched {$sentCount} reminder notifications.");

        return Command::SUCCESS;
    }
}
