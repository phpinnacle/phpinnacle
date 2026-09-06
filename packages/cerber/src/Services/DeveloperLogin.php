<?php

namespace PHPinnacle\Cerber\Services;

use Filament\Panel;
use Illuminate\Database\Eloquent\Model;
use PHPinnacle\Cerber\Models\User;

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

        if (!($user = User::find($credentials))) {
            return false;
        }

        if (!$user->canAccessPanel($panel)) {
            return false;
        }

        if ($tenant !== null && !$user->canAccessTenant($tenant)) {
            return false;
        }

        $auth = $panel->auth();

        if ($auth->check()) {
            $auth->logout();
        }

        $auth->login($user);

        session()->regenerate();

        return true;
    }
}
