<?php

namespace PHPinnacle\Cerber\Services;

use Filament\Exceptions\NoDefaultPanelSetException;
use Filament\Facades\Filament;
use Filament\PanelRegistry;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Auth;
use Lab404\Impersonate\Events\LeaveImpersonation;
use Lab404\Impersonate\Events\TakeImpersonation;
use Lab404\Impersonate\Services\ImpersonateManager;
use PHPinnacle\Cerber\Models\User;

readonly class ImpersonationSession
{
    public function __construct(
        private Session $session,
    ) {}

    public function leave(ImpersonateManager $manager): bool
    {
        if ($manager->leave()) {
            return true;
        }

        Auth::guard($manager->getImpersonatorGuardUsingName())->logout();
        $manager->clear();
        $this->clearAuthHashes();
        $this->session->forget('cerber.impersonator_user');

        return false;
    }

    public function takeImpersonation(TakeImpersonation $event): void
    {
        $this->clearAuthHashes();

        if (!$event->impersonator instanceof User) {
            return;
        }

        $manager = app(ImpersonateManager::class);
        $guard = $manager->getImpersonatorGuardName() ?? Auth::getDefaultDriver();
        $user = $event->impersonator;
        $this->session->put('cerber.impersonator_user', [
            'guard' => $guard,
            'id' => $user->id,
            'password' => $user->password,
            'remember_token' => $user->remember_token,
        ]);
    }

    public function leaveImpersonation(LeaveImpersonation $event): void
    {
        $this->clearAuthHashes();

        /** @var array{guard: string, id: string, password: ?string, remember_token: ?string}|null $original */
        $original = $this->session->pull('cerber.impersonator_user');

        if ($original === null) {
            return;
        }

        $user = User::find($original['id']);

        if (
            $user === null
            || !$user->status->canAccess()
            || !hash_equals((string) $user->password, (string) $original['password'])
            || !hash_equals((string) $user->remember_token, (string) $original['remember_token'])
        ) {
            Auth::guard($original['guard'])->logout();

            return;
        }
    }

    /**
     * A missing default Filament panel has no panel-specific authentication hash to clear.
     *
     * @mago-expect lint:no-empty-catch-clause
     */
    private function clearAuthHashes(): void
    {
        $hashes = [
            'password_hash_sanctum',
            'password_hash_' . Auth::getDefaultDriver(),
        ];

        $guard = $this->session->get('impersonate.guard');

        if ($guard) {
            $hashes[] = 'password_hash_' . $guard;
        }

        try {
            /** @throws NoDefaultPanelSetException */
            $hashes[] = 'password_hash_' . Filament::getCurrentOrDefaultPanel()->getAuthGuard();
        } catch (NoDefaultPanelSetException) {
        }

        $backToPanelId = $this->session->get('impersonate.back_to_panel');

        if ($backToPanelId) {
            $panel = app(PanelRegistry::class)->get($backToPanelId);

            if ($panel !== null) {
                $hashes[] = 'password_hash_' . $panel->getAuthGuard();
            }
        }

        $this->session->forget(array_unique($hashes));
    }
}
