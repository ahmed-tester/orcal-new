<?php

namespace Tests\Feature\Mail;

use App\Enums\Mail\MailMessageStatus;
use App\Enums\Mail\MailPriority;
use App\Models\Mail\MailExclusionKeyword;
use App\Models\Mail\MailImportanceKeyword;
use App\Models\Mail\MailTrustedDomain;
use App\Models\Mail\MailTrustedEmail;
use App\Services\Mail\EmailClassifierService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmailClassifierTest extends TestCase
{
    protected EmailClassifierService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        DB::purge();
        DB::reconnect('sqlite');
        DB::statement('PRAGMA foreign_keys = ON;');

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('trade_name')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->nullable();
            $table->string('type')->nullable();
            $table->string('label')->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();
        });

        $migration = require base_path('database/migrations/2026_11_19_130000_create_mail_inbox_tables.php');
        $migration->up();

        Cache::flush();
        $this->service = app(EmailClassifierService::class);
    }

    public function test_level1_trusted_email_and_wildcard_domain_are_important(): void
    {
        MailTrustedEmail::create([
            'email' => 'minister@gov.sa',
            'organization_name' => 'Royal Court',
            'is_active' => true,
        ]);

        $emailResult = $this->service->classify('MINISTER@gov.sa', 'Subject', 'Body');
        $this->assertTrue($emailResult['is_important']);
        $this->assertSame(100, $emailResult['confidence']);
        $this->assertSame('official', $emailResult['category']);
        $this->assertSame(MailPriority::HIGH, $emailResult['priority']);
        $this->assertSame(MailMessageStatus::NEW, $emailResult['status']);

        MailTrustedDomain::create([
            'domain_pattern' => '*.gov.sa',
            'organization_name' => 'Gov Entities',
            'is_active' => true,
        ]);

        $domainResult = $this->service->classify('official@zatca.gov.sa', 'Audit', 'Details');
        $this->assertTrue($domainResult['is_important']);
        $this->assertSame(100, $domainResult['confidence']);
        $this->assertSame(MailMessageStatus::NEW, $domainResult['status']);
    }

    public function test_level2_exclusion_overrides_importance_on_conflict(): void
    {
        MailExclusionKeyword::create(['keyword' => 'عرض خاص', 'is_active' => true]);
        MailImportanceKeyword::create(['keyword' => 'شكوى', 'weight' => 10, 'is_active' => true]);

        $result = $this->service->classify(
            'promo@marketing.test',
            'عرض خاص و شكوى',
            'تفاصيل العرض'
        );

        $this->assertFalse($result['is_important']);
        $this->assertSame(90, $result['confidence']);
        $this->assertSame('marketing', $result['category']);
        $this->assertSame(MailPriority::LOW, $result['priority']);
        $this->assertSame(MailMessageStatus::REJECTED, $result['status']);
    }

    public function test_level2_importance_keyword_detects_category_and_sets_new(): void
    {
        MailImportanceKeyword::create(['keyword' => 'شكوى', 'weight' => 10, 'is_active' => true]);

        $result = $this->service->classify(
            'client@random.test',
            'شكوى تأخر',
            'أرجو المتابعة'
        );

        $this->assertTrue($result['is_important']);
        $this->assertSame(85, $result['confidence']);
        $this->assertSame('complaint', $result['category']);
        $this->assertSame(MailPriority::HIGH, $result['priority']);
        $this->assertSame(MailMessageStatus::NEW, $result['status']);
    }

    public function test_level3_gemini_handles_important_rejected_low_confidence_and_caching(): void
    {
        config(['services.gemini.api_key' => 'test-gemini-key']);

        Http::fakeSequence()
            ->push([
                'candidates' => [[
                    'content' => ['parts' => [['text' => json_encode([
                        'is_important' => true,
                        'category' => 'طلب',
                        'confidence' => 88,
                        'summary' => 'طلب ترقية',
                        'suggested_priority' => 'عالية',
                    ])]]],
                ]],
            ], 200)
            ->push([
                'candidates' => [[
                    'content' => ['parts' => [['text' => json_encode([
                        'is_important' => false,
                        'category' => 'تسويقي',
                        'confidence' => 92,
                        'summary' => 'نشرة إعلانية',
                        'suggested_priority' => 'منخفضة',
                    ])]]],
                ]],
            ], 200)
            ->push([
                'candidates' => [[
                    'content' => ['parts' => [['text' => json_encode([
                        'is_important' => true,
                        'category' => 'استفسار',
                        'confidence' => 55,
                        'summary' => 'غير واضح',
                        'suggested_priority' => 'متوسطة',
                    ])]]],
                ]],
            ], 200);

        $important = $this->service->classify('vip@corp.test', 'Upgrade', 'Need 20 seats');
        $this->assertTrue($important['is_important']);
        $this->assertSame(MailMessageStatus::NEW, $important['status']);
        $this->assertSame('request', $important['category']);
        $this->assertSame(MailPriority::HIGH, $important['priority']);

        // Second identical call should hit cache without consuming the next HTTP fake response
        $cached = $this->service->classify('vip@corp.test', 'Upgrade', 'Need 20 seats');
        $this->assertSame($important['status'], $cached['status']);
        $this->assertSame(88, $cached['confidence']);

        $rejected = $this->service->classify('ads@shop.test', 'Sale', 'Big discount');
        $this->assertFalse($rejected['is_important']);
        $this->assertSame(MailMessageStatus::REJECTED, $rejected['status']);
        $this->assertSame('marketing', $rejected['category']);

        $underReview = $this->service->classify('maybe@unknown.test', 'Hello', 'Can we talk?');
        $this->assertSame(MailMessageStatus::UNDER_REVIEW, $underReview['status']);
        $this->assertSame(55, $underReview['confidence']);
    }

    public function test_level3_missing_key_daily_limit_and_api_failure_route_to_under_review(): void
    {
        config(['services.gemini.api_key' => '']);
        $noKey = $this->service->classify('a@b.test', 'Hi', 'Body');
        $this->assertSame(MailMessageStatus::UNDER_REVIEW, $noKey['status']);
        $this->assertSame(50, $noKey['confidence']);

        config(['services.gemini.api_key' => 'test-gemini-key']);
        Cache::put('mail_gemini_requests_'.now()->format('Y-m-d'), 1000, now()->endOfDay());
        $limitHit = $this->service->classify('c@d.test', 'Hi', 'Body');
        $this->assertSame(MailMessageStatus::UNDER_REVIEW, $limitHit['status']);
        $this->assertSame(50, $limitHit['confidence']);

        Cache::forget('mail_gemini_requests_'.now()->format('Y-m-d'));
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response(['error' => 'server error'], 500),
        ]);
        $failed = $this->service->classify('e@f.test', 'Hi', 'Body');
        $this->assertSame(MailMessageStatus::UNDER_REVIEW, $failed['status']);
        $this->assertSame(50, $failed['confidence']);
    }

    public function test_redaction_masks_cards_passwords_and_national_ids(): void
    {
        $raw = 'Card 4532 1234 5678 9012 password=MySecret123 ID 1098765432';
        $redacted = $this->service->redactSensitiveData($raw);

        $this->assertStringNotContainsString('4532', $redacted);
        $this->assertStringNotContainsString('MySecret123', $redacted);
        $this->assertStringNotContainsString('1098765432', $redacted);
    }

    public function test_company_rules_are_isolated_and_redaction_is_locale_independent(): void
    {
        $companyId = DB::table('companies')->insertGetId(['trade_name' => 'A']);
        MailTrustedEmail::create(['company_id' => $companyId, 'email' => 'private@gov.sa', 'is_active' => true]);
        config(['services.gemini.api_key' => '']);
        $this->assertSame(MailMessageStatus::UNDER_REVIEW, $this->service->classify('private@gov.sa', 'Notice', 'Body')['status']);
        $this->assertSame(MailMessageStatus::NEW, $this->service->classify('private@gov.sa', 'Notice', 'Body', $companyId)['status']);
        foreach (['ar', 'en'] as $locale) {
            app()->setLocale($locale);
            $terms = explode('|', __('mail_inbox.classifier.redaction.password_terms', [], 'ar'));
            $arabicTerm = str_replace('\\s*', ' ', $terms[3]);
            $redacted = $this->service->redactSensitiveData($arabicTerm.'=PrivateSecret password=OtherSecret');
            $this->assertStringNotContainsString('PrivateSecret', $redacted);
            $this->assertStringNotContainsString('OtherSecret', $redacted);
        }
    }

    public function test_importance_keyword_weight_selects_highest_weight_and_breaks_ties_by_id(): void
    {
        // Insert lower-weight keyword first to prove selection is independent of insertion order
        MailImportanceKeyword::create(['keyword' => 'استفسار', 'weight' => 4, 'is_active' => true]);
        MailImportanceKeyword::create(['keyword' => 'عاجل', 'weight' => 10, 'is_active' => true]);
        MailImportanceKeyword::create(['keyword' => 'غير مفعل', 'weight' => 100, 'is_active' => false]);

        $result = $this->service->classify('client@random.test', 'استفسار عاجل غير مفعل', 'تفاصيل');
        $this->assertTrue($result['is_important']);
        $this->assertSame(85, $result['confidence']);
        $this->assertSame('inquiry', $result['category']);
        $this->assertStringContainsString('عاجل', $result['classification_reason']);

        // Equal weights break ties deterministically by lowest id
        MailImportanceKeyword::create(['keyword' => 'عرض سعر', 'weight' => 15, 'is_active' => true]);
        MailImportanceKeyword::create(['keyword' => 'شكوى', 'weight' => 15, 'is_active' => true]);

        $tieResult = $this->service->classify('client@random.test', 'شكوى مع عرض سعر', 'تفاصيل');
        $this->assertStringContainsString('عرض سعر', $tieResult['classification_reason']);
    }
}
