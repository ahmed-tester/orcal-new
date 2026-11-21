<?php

namespace App\Services\Mail;

use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MailSettingsService
{
    public static function values(): array
    {
        return [
            'mail_gemini_model' => Setting::get('mail_gemini_model', Setting::getGeminiModel()),
            'mail_gemini_daily_limit' => (int) Setting::get('mail_gemini_daily_limit', 1000),
            'mail_escalation_hours' => (int) Setting::get('mail_escalation_hours', 4),
            'mail_enable_email_notifications' => filter_var(Setting::get('mail_enable_email_notifications', false), FILTER_VALIDATE_BOOLEAN),
            'mail_notification_email' => Setting::get('mail_notification_email'),
        ];
    }

    public static function save(array $data): void
    {
        abort_unless(auth()->user() && MailAccessService::canAdministerAllMail(auth()->user()), 403);
        $values = Validator::make($data, [
            'mail_gemini_model' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9.\-]+$/'],
            'mail_gemini_daily_limit' => ['required', 'integer', 'min:0'],
            'mail_escalation_hours' => ['required', 'integer', 'min:1'],
            'mail_enable_email_notifications' => ['required', 'boolean'],
            'mail_notification_email' => ['nullable', 'email', 'required_if:mail_enable_email_notifications,true'],
        ])->validate();

        DB::transaction(function () use ($values): void {
            foreach ($values as $key => $value) {
                Setting::set($key, is_bool($value) ? (int) $value : $value, 'mail', 'mail_inbox.settings.'.$key);
            }
        });
    }
}
