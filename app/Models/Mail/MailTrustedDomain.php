<?php

namespace App\Models\Mail;

use App\Models\Company;
use App\Observers\MailRuleObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([MailRuleObserver::class])]
class MailTrustedDomain extends Model
{
    use HasFactory;

    protected $table = 'trusted_domains';

    protected $fillable = [
        'company_id',
        'domain_pattern',
        'organization_name',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Match an email address against a wildcard domain pattern (e.g. *.gov.sa, moi.gov.*, @domain.com).
     */
    public static function matches(string $pattern, string $email): bool
    {
        $domain = substr(strrchr(strtolower(trim($email)), '@') ?: '', 1);
        if ($domain === '') {
            return false;
        }

        $pattern = ltrim(trim($pattern), '@');
        $regex = '/^'.str_replace('\*', '.*', preg_quote($pattern, '/')).'$/i';

        return (bool) preg_match($regex, $domain);
    }
}
