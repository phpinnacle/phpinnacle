<?php

namespace PHPinnacle\Cerber\Services;

use Filament\Panel;
use Illuminate\Database\Eloquent\Model;
use PHPinnacle\Cerber\Enums\LoginType;
use PHPinnacle\Cerber\Models\Login;

class DeveloperLogin
{
    /**
     * @param array<string, string> $developers
     */
    public function attempt(string $credentials, Panel $panel, ?Model $tenant, array $developers): bool
    {
        if (app()->isProduction()) {
            return false;
        }

        if (!array_key_exists($credentials, $developers)) {
            return false;
        }

        if (!($login = Login::lookup($credentials, LoginType::Email))) {
            return false;
        }

        if (!$login->canAccessPanel($panel)) {
            return false;
        }

        if ($tenant !== null && !$login->user->canAccessTenant($tenant)) {
            return false;
        }

        $auth = $panel->auth();

        if ($auth->check()) {
            $auth->logout();
        }

        $auth->login($login->user);

        session()->regenerate();

        return true;
    }
}
