<?php

namespace App\Console\Commands;

use App\Models\Debt;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckDebtDueDatesCommand extends Command
{
    protected $signature = 'debts:check-due-dates
                            {--days=7 : Horizon in days before due date to start alerting}
                            {--force : Force send alerts even if already processed today}';

    protected $description = 'Check unpaid debts, update overdue statuses, and send due date alerts to assigned employees';

    public function handle(): int
    {
        $today = Carbon::today();
        $daysHorizon = (int) $this->option('days');
        $this->info("Checking active debt due dates (Horizon: {$daysHorizon} days)...");

        $debts = Debt::with(['notifyEmployee.user', 'executor.user', 'company'])
            ->where('status', '!=', 'paid')
            ->whereNotNull('due_date')
            ->get();

        $processedCount = 0;
        $overdueCount = 0;

        foreach ($debts as $debt) {
            $dueDate = Carbon::parse($debt->due_date)->startOfDay();
            $daysRemaining = (int) $today->diffInDays($dueDate, false);
            $debtNumber = $debt->debt_number ?? ('#' . $debt->id);

            // 1. Automatically update status to 'overdue' if due date has passed
            if ($daysRemaining < 0 && $debt->status !== 'overdue') {
                $debt->status = 'overdue';
                $debt->saveQuietly();
                $overdueCount++;
            }

            // 2. Determine if debt qualifies for an alert (overdue, due today, or due within horizon)
            if ($daysRemaining > $daysHorizon) {
                continue;
            }

            // Determine recipient users
            $notifiableUsers = collect();

            // Primary target: Follow-up employee assigned to this debt
            if ($debt->notifyEmployee?->user) {
                $notifiableUsers->push($debt->notifyEmployee->user);
            }

            // Secondary target: The executor who recorded this debt
            if ($debt->executor?->user && (! $debt->notifyEmployee || $debt->executor->id !== $debt->notifyEmployee->id)) {
                $notifiableUsers->push($debt->executor->user);
            }

            // Super Admins & Owners: Always included so administrators can monitor due debts
            try {
                $superAdmins = User::all()->filter(fn ($u) => method_exists($u, 'isSuperAdminOrOwner') && $u->isSuperAdminOrOwner());
                $notifiableUsers = $notifiableUsers->concat($superAdmins);
            } catch (\Throwable $e) {
                // Ignore or log error
            }

            $notifiableUsers = $notifiableUsers->unique('id');

            if ($notifiableUsers->isEmpty()) {
                continue;
            }

            // 3. Compose notification title & body
            $debtorName = $debt->beneficiary_name ?? '—';
            $remaining = number_format((float) ($debt->remaining_amount ?? 0), 2) . ' SAR';

            [$title, $color] = match (true) {
                $daysRemaining < 0 => [
                    __('debts.notifications.overdue_title', ['number' => $debtNumber]),
                    'danger',
                ],
                $daysRemaining === 0 => [
                    __('debts.notifications.due_today_title', ['number' => $debtNumber]),
                    'warning',
                ],
                default => [
                    __('debts.notifications.upcoming_title', ['number' => $debtNumber, 'days' => $daysRemaining]),
                    'info',
                ],
            };

            $body = __('debts.notifications.body', [
                'debtor' => $debtorName,
                'remaining' => $remaining,
                'due_date' => $dueDate->format('Y-m-d'),
            ]);

            if (! empty($debt->notification_notes)) {
                $body .= "\n" . __('debts.fields.notification_notes') . ': ' . $debt->notification_notes;
            }

            // 4. Send Filament Database Notification
            $debtEditUrl = route('filament.admin.resources.debts.edit', ['record' => $debt->id]);

            foreach ($notifiableUsers as $user) {
                $notification = FilamentNotification::make()
                    ->title($title)
                    ->body($body)
                    ->icon('heroicon-o-bell')
                    ->status($color)
                    ->actions([
                        Action::make('view')
                            ->label(__('debts.actions.view_debt'))
                            ->url($debtEditUrl)
                            ->button(),
                    ]);

                // We use notifyNow to write directly and synchronously to the notifications table,
                // avoiding jobs getting stuck if the queue worker is not running on the server.
                $user->notifyNow($notification->toDatabase());
                \Filament\Notifications\Events\DatabaseNotificationsSent::dispatch($user);
            }

            $processedCount++;
        }

        $this->info("Completed. {$overdueCount} debt(s) marked overdue, {$processedCount} alert(s) dispatched.");
        Log::info("[Debts Due Date Check] Finished. Marked {$overdueCount} overdue, sent {$processedCount} alerts.");

        return Command::SUCCESS;
    }
}

