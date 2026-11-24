<?php

namespace App\Services\Mail;

use App\Enums\Mail\MailMessageStatus;
use App\Enums\Mail\MailPriority;
use App\Models\Mail\MailMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MailMessageService
{
    public const BULK_MOVE_LIMIT = 100;

    public const TAB_IMPORTANT = 'important';

    public const TAB_UNDER_REVIEW = 'under_review';

    public const TAB_SPAM_EXCLUDED = 'spam_excluded';

    public const TAB_NORMAL = 'inbox_normal';

    public const ACTION_CHANGE_CATEGORY = 'change_category';

    public function __construct(
        protected MailCategoryService $categoryService
    ) {}

    /**
     * Single source of truth for non-overlapping mail inbox tabs and badges.
     */
    public function applyTabScope(Builder $query, string $tab): Builder
    {
        $excludedSlugs = $this->categoryService->excludedSlugs();

        return match ($tab) {
            self::TAB_SPAM_EXCLUDED => $query->where(function (Builder $q) use ($excludedSlugs): void {
                $q->where('status', MailMessageStatus::REJECTED->value);
                if ($excludedSlugs !== []) {
                    $q->orWhereIn('category', $excludedSlugs);
                }
            }),
            self::TAB_UNDER_REVIEW => $this->applyNotExcludedScope($query, $excludedSlugs)
                ->where('status', MailMessageStatus::UNDER_REVIEW->value),
            self::TAB_IMPORTANT => $this->applyNotExcludedScope($query, $excludedSlugs)
                ->where('status', '!=', MailMessageStatus::UNDER_REVIEW->value)
                ->where('is_important', true),
            default => $query,
        };
    }

    public function applyNotExcludedScope(Builder $query, ?array $excludedSlugs = null): Builder
    {
        $excludedSlugs ??= $this->categoryService->excludedSlugs();

        $query->where('status', '!=', MailMessageStatus::REJECTED->value);

        if ($excludedSlugs !== []) {
            $query->whereNotIn('category', $excludedSlugs);
        }

        return $query;
    }

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

    public static function concurrencyToken(MailMessage $message): string
    {
        return md5(implode('|', [
            $message->status?->value ?? '',
            $message->priority?->value ?? '',
            (string) $message->category,
            $message->is_important ? '1' : '0',
            (string) ($message->assigned_to ?? ''),
            $message->updated_at?->format('Y-m-d H:i:s.u') ?? '',
        ]));
    }

    public function updateReview(MailMessage $message, array $data, User $actor, ?string $expectedVersion = null): void
    {
        Gate::forUser($actor)->authorize('review', $message);

        $values = Validator::make($data, [
            'status' => ['required', Rule::enum(MailMessageStatus::class)],
            'priority' => ['required', Rule::enum(MailPriority::class)],
            'category' => ['required', 'string'],
            'is_important' => ['required', 'boolean'],
            'assigned_to' => ['nullable', 'integer'],
        ])->validate();

        $categorySlug = strtolower(trim((string) $values['category']));
        $values['category'] = $categorySlug;

        if (! $this->categoryService->exists($categorySlug)) {
            throw ValidationException::withMessages([
                'category' => __('mail_inbox.validation.invalid_category'),
            ]);
        }

        if ($categorySlug !== strtolower((string) $message->category) && ! $this->categoryService->isActive($categorySlug)) {
            throw ValidationException::withMessages([
                'category' => __('mail_inbox.validation.inactive_category_forbidden'),
            ]);
        }

        $isExcluded = $this->categoryService->isExcluded($categorySlug);
        $statusValue = $values['status'] instanceof MailMessageStatus
            ? $values['status']->value
            : (string) $values['status'];
        $isImportant = (bool) $values['is_important'];

        if ($isImportant && ($isExcluded || in_array($statusValue, [MailMessageStatus::REJECTED->value, MailMessageStatus::UNDER_REVIEW->value], true))) {
            throw ValidationException::withMessages([
                'is_important' => __('mail_inbox.validation.important_conflict'),
            ]);
        }

        if ($statusValue === MailMessageStatus::UNDER_REVIEW->value && $isExcluded) {
            throw ValidationException::withMessages([
                'status' => __('mail_inbox.validation.under_review_excluded_conflict'),
            ]);
        }

        if (! empty($values['assigned_to'])) {
            $assignee = User::find($values['assigned_to']);
            if (! $assignee || ! MailAccessService::canViewMessage($assignee, $message)) {
                throw ValidationException::withMessages(['assigned_to' => __('mail_inbox.validation.assignee_not_allowed')]);
            }
        }

        DB::transaction(function () use ($message, $values, $expectedVersion): void {
            $locked = MailMessage::query()->whereKey($message->id)->lockForUpdate()->firstOrFail();

            if ($expectedVersion !== null && self::concurrencyToken($locked) !== $expectedVersion) {
                throw ValidationException::withMessages([
                    'status' => __('mail_inbox.validation.concurrent_modification'),
                ]);
            }

            $locked->update([
                'status' => $values['status'],
                'priority' => $values['priority'],
                'category' => $values['category'],
                'is_important' => (bool) $values['is_important'],
                'assigned_to' => $values['assigned_to'],
            ]);
        });
    }

    public function approveReview(MailMessage $message, User $actor, ?string $replacementCategory = null): void
    {
        Gate::forUser($actor)->authorize('review', $message);

        DB::transaction(function () use ($message, $replacementCategory): void {
            $locked = MailMessage::query()->whereKey($message->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== MailMessageStatus::UNDER_REVIEW) {
                throw ValidationException::withMessages(['status' => __('mail_inbox.validation.not_under_review')]);
            }

            $targetCategory = $locked->category;
            if ($this->categoryService->isExcluded($locked->category)) {
                $targetCategory = $this->validateNonExcludedReplacement($replacementCategory);
            }

            $locked->update([
                'status' => MailMessageStatus::NEW,
                'is_important' => true,
                'priority' => MailPriority::HIGH,
                'category' => $targetCategory,
            ]);
        });
    }

    public function rejectReview(MailMessage $message, User $actor): void
    {
        Gate::forUser($actor)->authorize('review', $message);

        DB::transaction(function () use ($message): void {
            $locked = MailMessage::query()->whereKey($message->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== MailMessageStatus::UNDER_REVIEW) {
                throw ValidationException::withMessages(['status' => __('mail_inbox.validation.not_under_review')]);
            }

            $locked->update([
                'status' => MailMessageStatus::REJECTED,
                'is_important' => false,
            ]);
        });
    }

    public function moveMessage(MailMessage $message, string $destination, User $actor, ?string $category = null): void
    {
        $this->moveMessagesBulk(collect([$message]), $destination, $actor, $category);
    }

    /**
     * @param  Collection<int, MailMessage>  $messages
     */
    public function moveMessagesBulk(Collection $messages, string $destination, User $actor, ?string $category = null): int
    {
        $allowedDestinations = [
            self::TAB_IMPORTANT,
            self::TAB_UNDER_REVIEW,
            self::TAB_SPAM_EXCLUDED,
            self::TAB_NORMAL,
            self::ACTION_CHANGE_CATEGORY,
        ];

        if (! in_array($destination, $allowedDestinations, true)) {
            throw ValidationException::withMessages([
                'destination' => __('mail_inbox.validation.invalid_destination'),
            ]);
        }

        if ($messages->isEmpty()) {
            return 0;
        }

        if ($messages->count() > self::BULK_MOVE_LIMIT) {
            throw ValidationException::withMessages([
                'destination' => __('mail_inbox.validation.bulk_limit_exceeded', ['limit' => self::BULK_MOVE_LIMIT]),
            ]);
        }

        foreach ($messages as $msg) {
            Gate::forUser($actor)->authorize('review', $msg);
        }

        $normalizedCategory = $category !== null ? strtolower(trim($category)) : null;
        if ($normalizedCategory === '') {
            $normalizedCategory = null;
        }

        if ($normalizedCategory !== null) {
            if (! $this->categoryService->exists($normalizedCategory) || ! $this->categoryService->isActive($normalizedCategory)) {
                throw ValidationException::withMessages([
                    'category' => __('mail_inbox.validation.inactive_category_forbidden'),
                ]);
            }

            if (in_array($destination, [self::TAB_IMPORTANT, self::TAB_UNDER_REVIEW, self::TAB_NORMAL], true)
                && $this->categoryService->isExcluded($normalizedCategory)) {
                throw ValidationException::withMessages([
                    'category' => __('mail_inbox.validation.excluded_category_not_allowed_for_destination'),
                ]);
            }
        } elseif ($destination === self::ACTION_CHANGE_CATEGORY) {
            throw ValidationException::withMessages([
                'category' => __('mail_inbox.validation.category_required'),
            ]);
        }

        $ids = $messages->pluck('id')->map(fn ($id): int => (int) $id)->unique()->sort()->values()->all();

        return DB::transaction(function () use ($ids, $destination, $normalizedCategory): int {
            $lockedMessages = MailMessage::query()
                ->whereIn('id', $ids)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $updated = 0;

            foreach ($lockedMessages as $locked) {
                $updates = $this->computeTransition($locked, $destination, $normalizedCategory);
                if ($updates !== []) {
                    $locked->update($updates);
                }
                $updated++;
            }

            return $updated;
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function computeTransition(MailMessage $message, string $destination, ?string $category): array
    {
        $currentStatus = $message->status;
        $currentIsExcluded = $this->categoryService->isExcluded($message->category);

        if ($destination === self::ACTION_CHANGE_CATEGORY) {
            $newCategory = (string) $category;
            $updates = ['category' => $newCategory];

            if ($this->categoryService->isExcluded($newCategory)) {
                $updates['is_important'] = false;
                if ($currentStatus === MailMessageStatus::UNDER_REVIEW) {
                    $updates['status'] = MailMessageStatus::REJECTED;
                }
            }

            return $updates;
        }

        if ($destination === self::TAB_SPAM_EXCLUDED) {
            $updates = [
                'status' => MailMessageStatus::REJECTED,
                'is_important' => false,
            ];

            if ($category !== null) {
                $updates['category'] = $category;
            }

            return $updates;
        }

        $targetCategory = $message->category;
        if ($currentIsExcluded) {
            if ($category === null) {
                throw ValidationException::withMessages([
                    'category' => __('mail_inbox.validation.replacement_category_required'),
                ]);
            }

            $targetCategory = $category;
        }

        if ($destination === self::TAB_UNDER_REVIEW) {
            return [
                'status' => MailMessageStatus::UNDER_REVIEW,
                'is_important' => false,
                'category' => $targetCategory,
            ];
        }

        $nextStatus = in_array($currentStatus, [MailMessageStatus::REJECTED, MailMessageStatus::UNDER_REVIEW], true)
            ? MailMessageStatus::NEW
            : $currentStatus;

        return [
            'status' => $nextStatus,
            'is_important' => $destination === self::TAB_IMPORTANT,
            'category' => $targetCategory,
        ];
    }

    protected function validateNonExcludedReplacement(?string $category): string
    {
        $normalized = strtolower(trim((string) $category));
        if ($normalized === '' || ! $this->categoryService->isActive($normalized) || $this->categoryService->isExcluded($normalized)) {
            throw ValidationException::withMessages([
                'category' => __('mail_inbox.validation.replacement_category_required'),
            ]);
        }

        return $normalized;
    }
}
