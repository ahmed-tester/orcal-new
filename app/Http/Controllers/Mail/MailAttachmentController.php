<?php

namespace App\Http\Controllers\Mail;

use App\Http\Controllers\Controller;
use App\Models\Mail\MailAttachment;
use App\Services\Mail\MailSyncService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MailAttachmentController extends Controller
{
    public function view(MailAttachment $attachment): StreamedResponse
    {
        Gate::authorize('view', $attachment);

        $disk = Storage::disk(MailSyncService::ATTACHMENT_DISK);
        if (! $disk->exists($attachment->file_path)) {
            abort(404);
        }

        $mimeType = $disk->mimeType($attachment->file_path);
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
        if (! in_array($mimeType, $allowedTypes, true)) {
            return $this->download($attachment);
        }

        return $disk->response($attachment->file_path, $attachment->file_name, [
            'Content-Type' => $mimeType,
            'Content-Security-Policy' => "sandbox; default-src 'none'; style-src 'unsafe-inline'",
            'Content-Disposition' => 'inline; filename="'.addslashes($attachment->file_name).'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function download(MailAttachment $attachment): StreamedResponse
    {
        Gate::authorize('view', $attachment);

        $disk = Storage::disk(MailSyncService::ATTACHMENT_DISK);
        if (! $disk->exists($attachment->file_path)) {
            abort(404);
        }

        return $disk->download($attachment->file_path, $attachment->file_name, [
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
