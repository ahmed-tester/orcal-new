<?php

namespace App\Models\Mail;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailAttachment extends Model
{
    use HasFactory;

    protected $table = 'email_attachments';

    protected $fillable = [
        'company_id',
        'email_id',
        'reply_id',
        'file_name',
        'file_path',
        'content_type',
        'file_size',
        'content_id',
        'is_inline',
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'email_id' => 'integer',
            'reply_id' => 'integer',
            'file_size' => 'integer',
            'is_inline' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(MailMessage::class, 'email_id');
    }

    public function reply(): BelongsTo
    {
        return $this->belongsTo(MailReply::class, 'reply_id');
    }

    public function getIsImageAttribute(): bool
    {
        $mime = strtolower((string) $this->content_type);
        $ext = strtolower(pathinfo((string) $this->file_name, PATHINFO_EXTENSION));

        return str_starts_with($mime, 'image/')
            || in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'], true);
    }

    public function getIsPdfAttribute(): bool
    {
        $mime = strtolower((string) $this->content_type);

        return $mime === 'application/pdf'
            || strtolower(pathinfo((string) $this->file_name, PATHINFO_EXTENSION)) === 'pdf';
    }

    public function getUrlAttribute(): string
    {
        return route('mail-attachments.view', $this);
    }

    public function getDownloadUrlAttribute(): string
    {
        return route('mail-attachments.download', $this);
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = (int) ($this->file_size ?? 0);

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1).' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }
}
