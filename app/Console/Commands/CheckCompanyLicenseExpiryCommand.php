<?php

namespace App\Console\Commands;

use App\Events\CompanyLicenseExpiryAlertEvent;
use App\Models\Company;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CheckCompanyLicenseExpiryCommand extends Command
{
    protected $signature = 'company:check-license-expiry
                            {--days= : Override check threshold with specific number of days}
                            {--force : Force send alerts regardless of exact milestone matching}';

    protected $description = 'Check and send dynamic 3-stage Company License expiry alerts based on system settings';

    public function handle(): int
    {
        $today = Carbon::today();
        $overrideDays = $this->option('days');
        $isForce = (bool) $this->option('force');

        // 1. Load Dynamic Alert Settings & Toggles from Database / Config (Same keys as Document/Iqama Alerts)
        $firstDays   = (int) $this->getSettingValue('iqama_alert_first_days', 90);
        $firstActive = (bool) $this->getSettingStatus('iqama_alert_first_days', 'iqama_alert_first_enabled', true);

        $secondDays   = (int) $this->getSettingValue('iqama_alert_second_days', 60);
        $secondActive = (bool) $this->getSettingStatus('iqama_alert_second_days', 'iqama_alert_second_enabled', true);

        $thirdDays   = (int) $this->getSettingValue('iqama_alert_third_days', 30);
        $thirdActive = (bool) $this->getSettingStatus('iqama_alert_third_days', 'iqama_alert_third_enabled', true);

        $activeLabel   = __('notifications.stages.active');
        $disabledLabel = __('notifications.stages.disabled');
        $daysUnit      = __('notifications.stages.days');

        $this->info(__('notifications.commands.company_loaded'));
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
        $this->line(__('notifications.commands.checking_company', ['date' => $targetDate->format('Y-m-d')]));

        $companies = Company::where(function ($q) use ($targetDate) {
            $q->whereNotNull('commercial_registration_expiry')->where('commercial_registration_expiry', '<=', $targetDate->format('Y-m-d'))
              ->orWhere(fn($sq) => $sq->whereNotNull('municipality_license_expiry')->where('municipality_license_expiry', '<=', $targetDate->format('Y-m-d')))
              ->orWhere(fn($sq) => $sq->whereNotNull('civil_defense_license_expiry')->where('civil_defense_license_expiry', '<=', $targetDate->format('Y-m-d')));
        })->get();

        $processedCount = 0;

        foreach ($companies as $company) {
            $licenses = [
                'cr' => [
                    'label'  => __('notifications.documents.cr'),
                    'number' => $company->commercial_registration_number,
                    'expiry' => $company->commercial_registration_expiry,
                ],
                'municipality' => [
                    'label'  => __('notifications.documents.municipality'),
                    'number' => $company->municipality_license_number,
                    'expiry' => $company->municipality_license_expiry,
                ],
                'civil_defense' => [
                    'label'  => __('notifications.documents.civil_defense'),
                    'number' => $company->civil_defense_license_number,
                    'expiry' => $company->civil_defense_license_expiry,
                ],
            ];

            foreach ($licenses as $typeKey => $license) {
                if (empty($license['expiry'])) {
                    continue;
                }

                $expiry = Carbon::parse($license['expiry']);
                $daysRemaining = (int) $today->diffInDays($expiry, false);
                $typeLabel = $license['label'];

                $stageLabel = null;

                // 2. Classify Alert Stage based on System Settings
                if ($overrideDays !== null || $isForce) {
                    $stageLabel = match (true) {
                        $daysRemaining <= $thirdDays  => __('notifications.stages.critical_alert', ['type' => $typeLabel, 'days' => $daysRemaining]),
                        $daysRemaining <= $secondDays => __('notifications.stages.second_alert', ['type' => $typeLabel, 'days' => $daysRemaining]),
                        default                       => __('notifications.stages.first_alert', ['type' => $typeLabel, 'days' => $daysRemaining]),
                    };
                } else {
                    if ($firstActive && $daysRemaining === $firstDays) {
                        $stageLabel = __('notifications.stages.first_alert', ['type' => $typeLabel, 'days' => $firstDays]);
                    } elseif ($secondActive && $daysRemaining === $secondDays) {
                        $stageLabel = __('notifications.stages.second_alert', ['type' => $typeLabel, 'days' => $secondDays]);
                    } elseif ($thirdActive && $daysRemaining <= $thirdDays) {
                        $stageLabel = $daysRemaining < 0 
                            ? __('notifications.stages.expired_alert', ['type' => $typeLabel])
                            : __('notifications.stages.critical_alert', ['type' => $typeLabel, 'days' => $daysRemaining]);
                    }
                }

                if (!$stageLabel) {
                    continue;
                }

                $compName = $company->trade_name ?? $company->name ?? ('#' . $company->id);
                $licNo = $license['number'] ?? '—';

                $this->line("🔔 <comment>[{$stageLabel}]</comment> {$compName} | {$typeLabel} ({$licNo}) | {$expiry->format('Y-m-d')} (" . __('notifications.stages.days_left', ['days' => $daysRemaining]) . ")");

                // Dispatch Event
                event(new CompanyLicenseExpiryAlertEvent(
                    company: $company,
                    licenseType: $typeLabel,
                    licenseNumber: $licNo,
                    expiryDate: $expiry->format('Y-m-d'),
                    daysRemaining: $daysRemaining,
                    alertStage: $stageLabel
                ));

                $processedCount++;
            }
        }

        $this->newLine();
        $this->info(__('notifications.commands.company_done', ['count' => $processedCount]));
        Log::info("[Company License Check Command] Processed {$processedCount} dynamic alert(s).");

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
