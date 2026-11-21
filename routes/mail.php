<?php

use App\Http\Controllers\Mail\MailAttachmentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/mail-attachments/{attachment}/view', [MailAttachmentController::class, 'view'])
        ->name('mail-attachments.view');
    Route::get('/mail-attachments/{attachment}/download', [MailAttachmentController::class, 'download'])
        ->name('mail-attachments.download');
});
