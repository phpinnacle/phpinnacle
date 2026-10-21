<?php

namespace PHPinnacle\Cerber\Services;

use Filament\Exceptions\NoDefaultPanelSetException;
use Filament\Facades\Filament;
use Filament\PanelRegistry;

class ImpersonationSession
{
    /**
     * A missing default Filament panel has no panel-specific authentication hash to clear.
     *
     * @mago-expect lint:no-empty-catch-clause
     */
    public function clearAuthHashes(): void
    {
        $hashes = [
            'password_hash_sanctum',
            'password_hash_' . auth()->getDefaultDriver(),
        ];

        $guard = session('impersonate.guard');

        if ($guard) {
            $hashes[] = 'password_hash_' . $guard;
        }

        try {
            /** @throws NoDefaultPanelSetException */
            $hashes[] = 'password_hash_' . Filament::getCurrentOrDefaultPanel()->getAuthGuard();
        } catch (NoDefaultPanelSetException) {
        }

        $backToPanelId = session()->get('impersonate.back_to_panel');

        if ($backToPanelId) {
            $panel = app(PanelRegistry::class)->get($backToPanelId);

            if ($panel !== null) {
                $hashes[] = 'password_hash_' . $panel->getAuthGuard();
            }
        }

        session()->forget(array_unique($hashes));
    }
}
