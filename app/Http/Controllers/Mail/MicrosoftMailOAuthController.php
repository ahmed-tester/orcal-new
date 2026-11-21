<?php

namespace App\Http\Controllers\Mail;

use App\Http\Controllers\Controller;
use App\Models\Mail\MailAccount;
use App\Services\Mail\MicrosoftMailOAuthService;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MicrosoftMailOAuthController extends Controller
{
    public function redirect(
        MailAccount $account,
        Request $request,
        MicrosoftMailOAuthService $oauthService,
    ): RedirectResponse {
        $result = $oauthService->beginAuthorization(
            $account,
            $request->user(),
            $request->input('return_url')
        );

        return redirect()->away($result['url']);
    }

    public function callback(
        Request $request,
        MicrosoftMailOAuthService $oauthService,
    ): RedirectResponse {
        $result = $oauthService->handleCallback($request, $request->user());

        Notification::make()
            ->title($result['message'])
            ->{$result['success'] ? 'success' : 'danger'}()
            ->send();

        return redirect()->to($result['redirect_url']);
    }
}
