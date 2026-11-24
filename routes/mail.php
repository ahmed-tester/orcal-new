<?php

use App\Http\Controllers\Mail\MailAttachmentController;
use App\Http\Controllers\Mail\MicrosoftMailOAuthController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/mail-attachments/{attachment}/view', [MailAttachmentController::class, 'view'])
        ->name('mail-attachments.view');
    Route::get('/mail-attachments/{attachment}/download', [MailAttachmentController::class, 'download'])
        ->name('mail-attachments.download');

    Route::post('/mail/microsoft/{account}/redirect', [MicrosoftMailOAuthController::class, 'redirect'])
        ->name('mail.microsoft.redirect');
    Route::get('/mail/microsoft/callback', [MicrosoftMailOAuthController::class, 'callback'])
        ->name('mail.microsoft.callback');
    Route::get('/mail/oauth/microsoft/callback', [MicrosoftMailOAuthController::class, 'callback'])
        ->name('mail.oauth.microsoft.callback');
});
