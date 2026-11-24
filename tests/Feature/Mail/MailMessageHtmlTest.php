<?php

namespace Tests\Feature\Mail;

use App\Models\Mail\MailMessage;
use Livewire\Features\SupportAutoInjectedAssets\SupportAutoInjectedAssets;
use Tests\TestCase;

class MailMessageHtmlTest extends TestCase
{
    public function test_email_document_is_embedded_without_duplicating_livewire_assets(): void
    {
        $message = new MailMessage([
            'body_html' => '<html><head><title>Email title</title><style>body { display: block }</style></head><body><table dir="rtl" style="color: red"><tr><td>Hello <a href="https://example.com" onclick="evil()">Link</a><img src="cid:logo" width="200"><script>alert(1)</script></td></tr></table></body></html>',
        ]);

        $html = $message->sanitized_html;

        $this->assertDoesNotMatchRegularExpression('/<\/?(?:html|head|body)\b/i', $html);
        $this->assertStringNotContainsString('Email title', $html);
        $this->assertStringNotContainsString('<style', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('dir="rtl"', $html);
        $this->assertStringContainsString('style="color: red"', $html);
        $this->assertStringContainsString('Hello', $html);
        $this->assertStringContainsString('src="cid:logo"', $html);
        $this->assertStringContainsString('target="_blank"', $html);

        $page = '<html><head></head><body><div>'.$html.'</div></body></html>';
        $script = '<script src="/livewire.js"></script>';
        $rendered = SupportAutoInjectedAssets::injectAssets($page, '', $script);

        $this->assertSame(1, substr_count($rendered, $script));
        $this->assertStringContainsString('</div>'.$script.'</body>', $rendered);
    }

    public function test_html_fragment_and_plain_text_fallback_remain_supported(): void
    {
        $message = new MailMessage(['body_html' => '<div dir="ltr"><p>Existing fragment</p></div>']);

        $this->assertSame('<div dir="ltr"><p>Existing fragment</p></div>', $message->sanitized_html);

        $message->body_html = '<html><head><title>Empty</title></head><body><script>evil()</script></body></html>';
        $message->body_plain = "Safe <text>\nSecond line";

        $this->assertSame("Safe &lt;text&gt;<br />\nSecond line", $message->sanitized_html);
    }
}
