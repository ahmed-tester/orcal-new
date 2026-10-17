<?php

namespace App\Console\Commands;

use App\Events\IqamaExpiryAlertEvent;
use App\Models\EmployeeDetail;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CheckIqamaExpiryCommand extends Command
{
    protected $signature = 'iqama:check-expiry 
                            {--days= : Override check threshold with specific number of days}
                            {--force : Force send alerts regardless of exact milestone matching}';

    protected $description = 'Check and send dynamic 3-stage Iqama expiry alerts based on system settings';

    public function handle(): int
    {
        $today = Carbon::today();
        $overrideDays = $this->option('days');
        $isForce = (bool) $this->option('force');

        // 1. Load Dynamic Alert Settings & Toggles from Database / Config
        $firstDays   = (int) $this->getSettingValue('iqama_alert_first_days', 90);
        $firstActive = (bool) $this->getSettingStatus('iqama_alert_first_days', 'iqama_alert_first_enabled', true);

        $secondDays   = (int) $this->getSettingValue('iqama_alert_second_days', 60);
        $secondActive = (bool) $this->getSettingStatus('iqama_alert_second_days', 'iqama_alert_second_enabled', true);

        $thirdDays   = (int) $this->getSettingValue('iqama_alert_third_days', 30);
        $thirdActive = (bool) $this->getSettingStatus('iqama_alert_third_days', 'iqama_alert_third_enabled', true);

        $activeLabel   = __('notifications.stages.active');
        $disabledLabel = __('notifications.stages.disabled');
        $daysUnit      = __('notifications.stages.days');

        $this->info(__('notifications.commands.iqama_loaded'));
        $this->line("   • " . __('notifications.stages.stage_1') . ": {$firstDays} {$daysUnit} [" . ($firstActive ? "<info>{$activeLabel}</info>" : "<comment>{$disabledLabel}</comment>") . ']');
        $this->line("   • " . __('notifications.stages.stage_2') . ": {$secondDays} {$daysUnit} [" . ($secondActive ? "<info>{$activeLabel}</info>" : "<comment>{$disabledLabel}</comment>") . ']');
        $this->line("   • " . __('notifications.stages.stage_3') . ": {$thirdDays} {$daysUnit} [" . ($thirdActive ? "<info>{$activeLabel}</info>" : "<comment>{$disabledLabel}</comment>") . ']');

        // Determine max search horizon in DB
        if ($overrideDays !== null) {
            $maxHorizonDays = (int) $overrideDays;
            $this->warn(__('notifications.commands.manual_override', ['days' => $maxHorizonDays]));
        } else {
            $activeThresholds = [];
            if ($firstActive) $activeThresholds[] = $firstDays;
            if ($secondActive) $activeThresholds[] = $secondDays;
            if ($thirdActive) $activeThresholds[] = $thirdDays;

            $maxHorizonDays = !empty($activeThresholds) ? max($activeThresholds) : 90;
        }

        $targetDate = $today->copy()->addDays($maxHorizonDays);
        $this->line(__('notifications.commands.checking_iqama', ['date' => $targetDate->format('Y-m-d')]));

        // Fetch employee details
        $expiringDetails = EmployeeDetail::with(['employee.user', 'employee.company.owners.user'])
            ->whereNotNull('iqama_expiry')
            ->where('iqama_expiry', '<=', $targetDate->format('Y-m-d'))
            ->get();

        $processedCount = 0;

        foreach ($expiringDetails as $detail) {
            $employee = $detail->employee;
            if (!$employee || ($employee->status && $employee->status->value !== 'active')) {
                continue;
            }

            $expiry = Carbon::parse($detail->iqama_expiry);
            $daysRemaining = (int) $today->diffInDays($expiry, false);

            $stageLabel = null;

            // 2. Classify Alert Stage
            if ($overrideDays !== null || $isForce) {
                // Manual override mode
                $stageLabel = match (true) {
                    $daysRemaining <= $thirdDays  => __('notifications.stages.iqama_critical_alert', ['days' => $daysRemaining]),
                    $daysRemaining <= $secondDays => __('notifications.stages.iqama_second_alert', ['days' => $daysRemaining]),
                    default                       => __('notifications.stages.iqama_first_alert', ['days' => $daysRemaining]),
                };
            } else {
                // Daily Milestone Mode
                if ($firstActive && $daysRemaining === $firstDays) {
                    $stageLabel = __('notifications.stages.iqama_first_alert', ['days' => $firstDays]);
                } elseif ($secondActive && $daysRemaining === $secondDays) {
                    $stageLabel = __('notifications.stages.iqama_second_alert', ['days' => $secondDays]);
                } elseif ($thirdActive && $daysRemaining <= $thirdDays) {
                    $stageLabel = $daysRemaining < 0 
                        ? __('notifications.stages.iqama_expired_alert') 
                        : __('notifications.stages.iqama_critical_alert', ['days' => $daysRemaining]);
                }
            }

            if (!$stageLabel) {
                continue;
            }

            $empName = $employee->user?->name ?? ('#' . $employee->id);
            $iqamaNo = $detail->iqama_number ?? '—';

            $this->line("🔔 <comment>[{$stageLabel}]</comment> Employee: {$empName} | Iqama: {$iqamaNo} | Expiry: {$expiry->format('Y-m-d')} (" . __('notifications.stages.days_remaining', ['days' => $daysRemaining]) . ")");

            // Dispatch Event
            event(new IqamaExpiryAlertEvent(
                employee: $employee,
                detail: $detail,
                daysRemaining: $daysRemaining,
                alertStage: $stageLabel
            ));

            $processedCount++;
        }

        $this->newLine();
        $this->info(__('notifications.commands.iqama_done', ['count' => $processedCount]));
        Log::info("[Iqama Check Command] Processed {$processedCount} dynamic alert(s).");

        return Command::SUCCESS;
    }

    /**
     * Retrieve setting numeric value with fallback.
     */
    protected function getSettingValue(string $key, mixed $default = null): mixed
    {
        if (class_exists(Setting::class)) {
            $val = Setting::get($key);
            if ($val !== null && $val !== '') {
                return $val;
            }
        }

        if (function_exists('setting')) {
            $val = setting($key);
            if ($val !== null) return $val;
        }

        if (Schema::hasTable('settings')) {
            $row = DB::table('settings')->where('key', $key)->first();
            if ($row) {
                $val = $row->value ?? $row->payload ?? null;
                if ($val !== null && $val !== '') return $val;
            }
        }

        return config("settings.{$key}", $default);
    }

    /**
     * Check if a setting is enabled/active.
     */
    protected function getSettingStatus(string $daysKey, string $statusKey, bool $default = true): bool
    {
        if (class_exists(Setting::class)) {
            if (Setting::has($daysKey)) {
                return Setting::isActive($daysKey);
            }
        }

        if (Schema::hasTable('settings')) {
            $mainRow = DB::table('settings')->where('key', $daysKey)->first();
            if ($mainRow) {
                if (isset($mainRow->is_active)) return (bool) $mainRow->is_active;
                if (isset($mainRow->status)) return in_array($mainRow->status, [1, '1', 'active', 'enabled', true], true);
                if (isset($mainRow->enabled)) return (bool) $mainRow->enabled;
            }

            $statusRow = DB::table('settings')->where('key', $statusKey)->first();
            if ($statusRow) {
                $val = $statusRow->value ?? $statusRow->payload ?? null;
                if ($val !== null) {
                    return filter_var($val, FILTER_VALIDATE_BOOLEAN);
                }
            }
        }

        return (bool) config("settings.{$statusKey}", $default);
    }
}
