<?php

namespace App\Services\Mail;

use App\Enums\Mail\MailCategory;
use App\Enums\Mail\MailMessageStatus;
use App\Enums\Mail\MailPriority;
use App\Models\Mail\MailMessage;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MailMessageService
{
    public function updateFollowUp(MailMessage $message, array $data, User $actor): void
    {
        Gate::forUser($actor)->authorize('review', $message);
        $values = Validator::make($data, [
            'status' => ['required', Rule::in([MailMessageStatus::NEW->value, MailMessageStatus::FOLLOWING_UP->value])],
            'assigned_to' => ['nullable', 'integer'],
        ])->validate();
        if (! empty($values['assigned_to'])) {
            $assignee = User::find($values['assigned_to']);
            if (! $assignee || ! MailAccessService::canViewMessage($assignee, $message)) {
                throw ValidationException::withMessages(['assigned_to' => __('mail_inbox.validation.assignee_not_allowed')]);
            }
        }

        $message->update($values);
    }

    public function updateReview(MailMessage $message, array $data, User $actor): void
    {
        Gate::forUser($actor)->authorize('review', $message);

        $values = Validator::make($data, [
            'status' => ['required', Rule::enum(MailMessageStatus::class)],
            'priority' => ['required', Rule::enum(MailPriority::class)],
            'category' => ['required', Rule::enum(MailCategory::class)],
            'is_important' => ['required', 'boolean'],
            'assigned_to' => ['nullable', 'integer'],
        ])->validate();

        if (! empty($values['assigned_to'])) {
            $assignee = User::find($values['assigned_to']);
            if (! $assignee || ! MailAccessService::canViewMessage($assignee, $message)) {
                throw ValidationException::withMessages(['assigned_to' => __('mail_inbox.validation.assignee_not_allowed')]);
            }
        }

        $message->update($values);
    }

    public function approveReview(MailMessage $message, User $actor): void
    {
        Gate::forUser($actor)->authorize('review', $message);

        if ($message->status !== MailMessageStatus::UNDER_REVIEW) {
            throw ValidationException::withMessages(['status' => __('mail_inbox.validation.not_under_review')]);
        }

        $message->update([
            'status' => MailMessageStatus::NEW,
            'is_important' => true,
            'priority' => MailPriority::HIGH,
        ]);
    }

    public function rejectReview(MailMessage $message, User $actor): void
    {
        Gate::forUser($actor)->authorize('review', $message);

        if ($message->status !== MailMessageStatus::UNDER_REVIEW) {
            throw ValidationException::withMessages(['status' => __('mail_inbox.validation.not_under_review')]);
        }

        $message->update([
            'status' => MailMessageStatus::REJECTED,
            'is_important' => false,
        ]);
    }
}
