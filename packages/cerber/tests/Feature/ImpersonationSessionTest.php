<?php

use Filament\Facades\Filament;
use Filament\Panel;
use Filament\PanelRegistry;
use Illuminate\Support\Facades\Event;
use Lab404\Impersonate\Events\LeaveImpersonation;
use Lab404\Impersonate\Events\TakeImpersonation;
use PHPinnacle\Cerber\Models\User;
use Tests\TestCase;

uses(TestCase::class);

it('clears affected authentication hashes on impersonation events', function (string $event) {
    $panel = Panel::make()->id('admin')->default()->authGuard('admin');
    app(PanelRegistry::class)->register($panel);
    app(PanelRegistry::class)->register(Panel::make()->id('back')->authGuard('back'));
    Filament::setCurrentPanel($panel);
    session()->put([
        'impersonate.guard' => 'original',
        'impersonate.back_to_panel' => 'back',
        'password_hash_sanctum' => 'hash',
        'password_hash_' . auth()->getDefaultDriver() => 'hash',
        'password_hash_admin' => 'hash',
        'password_hash_back' => 'hash',
        'password_hash_original' => 'hash',
        'password_hash_unrelated' => 'keep',
    ]);

    Event::dispatch(new $event(new User, new User));

    expect(session()->all())
        ->not
        ->toHaveKeys([
            'password_hash_sanctum',
            'password_hash_' . auth()->getDefaultDriver(),
            'password_hash_admin',
            'password_hash_back',
            'password_hash_original',
        ])
        ->and(session('password_hash_unrelated'))
        ->toBe('keep');
})->with([TakeImpersonation::class, LeaveImpersonation::class]);

it('clears authentication hashes without a Filament panel', function () {
    session()->put('password_hash_sanctum', 'hash');
    session()->put('password_hash_' . auth()->getDefaultDriver(), 'hash');

    Event::dispatch(new TakeImpersonation(new User, new User));

    expect(session()->has('password_hash_sanctum'))
        ->toBeFalse()
        ->and(session()->has('password_hash_' . auth()->getDefaultDriver()))
        ->toBeFalse();
});
