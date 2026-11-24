<?php

namespace App\Models\Mail;

use App\Enums\Mail\MailCategory as LegacyMailCategory;
use App\Enums\Mail\MailMessageStatus;
use App\Enums\Mail\MailPriority;
use App\Enums\Mail\MailSourceFolder;
use App\Models\Company;
use App\Models\User;
use App\Services\Mail\MailCategoryService;
use App\Services\Mail\MailNotificationService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class MailMessage extends Model
{
    use HasFactory;

    protected $table = 'emails';

    protected $fillable = [
        'company_id',
        'email_account_id',
        'conversation_id',
        'message_id',
        'in_reply_to',
        'references',
        'sender_email',
        'sender_name',
        'recipient_email',
        'subject',
        'body_plain',
        'body_html',
        'received_at',
        'source_folder',
        'is_important',
        'category',
        'confidence',
        'summary',
        'priority',
        'status',
        'classification_reason',
        'is_read',
        'replied_at',
        'assigned_to',
        'escalated_at',
        'fingerprint',
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'email_account_id' => 'integer',
            'conversation_id' => 'integer',
            'received_at' => 'datetime',
            'replied_at' => 'datetime',
            'escalated_at' => 'datetime',
            'source_folder' => MailSourceFolder::class,
            'is_important' => 'boolean',
            'confidence' => 'integer',
            'priority' => MailPriority::class,
            'status' => MailMessageStatus::class,
            'is_read' => 'boolean',
            'assigned_to' => 'integer',
        ];
    }

    public function setCategoryAttribute(mixed $value): void
    {
        if ($value instanceof LegacyMailCategory) {
            $this->attributes['category'] = $value->value;

            return;
        }

        $normalized = strtolower(trim((string) $value));
        $this->attributes['category'] = $normalized !== '' ? $normalized : MailCategory::SYSTEM_OTHER_SLUG;
    }

    public function categoryDefinition(): BelongsTo
    {
        return $this->belongsTo(MailCategory::class, 'category', 'slug');
    }

    public function categoryLabel(?string $locale = null): string
    {
        return app(MailCategoryService::class)->labelFor($this->category, $locale);
    }

    public function categoryColor(): string
    {
        return app(MailCategoryService::class)->colorFor($this->category);
    }

    public function isExcludedCategory(): bool
    {
        return app(MailCategoryService::class)->isExcluded($this->category);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(MailAccount::class, 'email_account_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(MailConversation::class, 'conversation_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(MailReply::class, 'email_id')->orderBy('created_at', 'asc');
    }

    public function internalNotes(): HasMany
    {
        return $this->hasMany(MailInternalNote::class, 'email_id')->orderBy('created_at', 'desc');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MailAttachment::class, 'email_id');
    }

    public function regularAttachments(): HasMany
    {
        return $this->hasMany(MailAttachment::class, 'email_id')
            ->whereNull('reply_id')
            ->where(function ($q) {
                $q->where('is_inline', false)
                    ->orWhere('content_type', 'not like', 'image/%');
            });
    }

    /**
     * Return safe sanitized HTML body to prevent XSS and strip tracking pixels.
     */
    public function getSanitizedHtmlAttribute(): string
    {
        $html = trim((string) ($this->body_html ?? ''));
        if ($html !== '') {
            $config = (new HtmlSanitizerConfig)
                ->allowSafeElements()
                // Email documents are embedded fragments, not additional page bodies.
                ->blockElement('html')
                ->blockElement('body')
                ->dropElement('head')
                ->allowRelativeLinks()
                ->allowRelativeMedias()
                ->allowMediaSchemes(['http', 'https', 'cid', 'data'])
                ->allowAttribute('style', '*')
                ->allowAttribute('dir', '*')
                ->allowAttribute('class', '*')
                ->forceAttribute('a', 'target', '_blank')
                ->forceAttribute('a', 'rel', 'noopener noreferrer nofollow');

            $cleaned = (new HtmlSanitizer($config))->sanitize($html);

            $cleaned = preg_replace(
                '/<img[^>]*(width\s*=\s*["\']?[01]["\']?|height\s*=\s*["\']?[01]["\']?|style\s*=\s*["\'][^"\']*(display\s*:\s*none|width\s*:\s*[01]px|height\s*:\s*[01]px)[^"\']*")[^>]*>/i',
                '',
                $cleaned
            ) ?? $cleaned;

            if (trim(strip_tags($cleaned)) !== '') {
                return $cleaned;
            }
        }

        return nl2br(e((string) ($this->body_plain ?? '')));
    }

    protected static function booted(): void
    {
        static::deleting(function (MailMessage $message): void {
            app(MailNotificationService::class)->deleteNotificationsForRecords(
                MailMessage::class,
                [$message->id]
            );
        });
    }
}
