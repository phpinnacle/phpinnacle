<?php

namespace PHPinnacle\Cerber\Resources\Users\Actions;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Lab404\Impersonate\Services\ImpersonateManager;
use PHPinnacle\Cerber\Models\User;

class ImpersonateAction extends Action
{
    public static function getDefaultName(): string
    {
        return 'cerber_user_impersonate';
    }

    public function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('phpinnacle-cerber::resources.user.actions.impersonate'))
            ->icon('phosphor-user-switch')
            ->disabled($this->cantBeImpersonated(...))
            ->action($this->impersonate(...));
    }

    private function cantBeImpersonated(User $record, ImpersonateManager $manager): bool
    {
        return $record->is(Filament::auth()->user()) || $manager->isImpersonating();
    }

    private function impersonate(User $record, ImpersonateManager $manager): bool|RedirectResponse
    {
        if ($this->cantBeImpersonated($record, $manager)) {
            return false;
        }

        $panel = Filament::getCurrentOrDefaultPanel();
        $guard = $panel->getAuthGuard();

        session()->put([
            'impersonate.back_to' => request('fingerprint.path', request()->header('referer')) ?? $panel->getUrl(),
            'impersonate.guard' => $guard,
        ]);

        $manager->take(Filament::auth()->user(), $record, $guard);

        return redirect('/');
    }
}
