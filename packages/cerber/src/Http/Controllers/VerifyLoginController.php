<?php

namespace PHPinnacle\Cerber\Http\Controllers;

use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use PHPinnacle\Cerber\Models\Login;

class VerifyLoginController
{
    public function __invoke(Request $request, Login $login): RedirectResponse
    {
        abort_unless($login->matchesVerificationHash((string) $request->query('hash')), 403);
        $login->markVerified();
        Notification::make()->title(__('phpinnacle-cerber::logins.verified'))->success()->send();

        return redirect(Filament::getCurrentOrDefaultPanel()->getLoginUrl());
    }
}
