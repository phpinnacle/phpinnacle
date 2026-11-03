<?php

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Route;
use PHPinnacle\Cerber\Enums\LoginType;
use PHPinnacle\Cerber\Models\Login;
use PHPinnacle\Cerber\Models\Tenant;
use PHPinnacle\Cerber\Models\User;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    config([
        'auth.guards.web' => ['driver' => 'session', 'provider' => 'users'],
        'auth.providers.users' => ['driver' => 'cerber', 'model' => User::class],
    ]);
    $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    $this->artisan('migrate')->assertSuccessful();
    Tenant::default('default');
    $this->user = User::register('User', ['admin']);
    $this->user->password = 'shared-password';
    $this->user->save();
});

it('authenticates several typed logins with one user password', function () {
    Login::register($this->user, LoginType::Email, 'first@example.test')->markVerified();
    Login::register($this->user, LoginType::Email, 'second@example.test')->markVerified();
    Login::register($this->user, LoginType::Phone, '+48123456789');
    Login::register($this->user, LoginType::Login, 'first@example.test');

    foreach ([
        ['email', 'FIRST@example.test'],
        ['email', 'second@example.test'],
        ['phone', '+48123456789'],
        ['login', 'first@example.test'],
    ] as [$type, $value]) {
        expect(auth()->attempt(['type' => $type, 'value' => $value, 'password' => 'shared-password']))->toBeTrue();
        expect(auth()->user())->toBeInstanceOf(User::class)->and(auth()->id())->toBe($this->user->id);
        auth()->logout();
    }

    expect(auth()->attempt(['email' => 'first@example.test', 'password' => 'wrong-password']))->toBeFalse();
    expect($this->user->toArray())->not->toHaveKeys(['password', 'remember_token']);
});

it('requires email verification and clears it when the address changes', function () {
    $login = Login::register($this->user, LoginType::Email, 'first@example.test');
    $credentials = ['email' => 'first@example.test', 'password' => 'shared-password'];

    expect(auth()->attempt($credentials))->toBeFalse();
    $login->markVerified();
    expect(auth()->attempt($credentials))->toBeTrue();
    auth()->logout();
    $login->update(['value' => 'changed@example.test']);

    expect($login->fresh()->verified_at)->toBeNull();
    expect(auth()->attempt([...$credentials, 'email' => 'changed@example.test']))->toBeFalse();
});

it('restores user sessions independently of their sign-in login', function () {
    $login = Login::register($this->user, LoginType::Phone, '+48123456789');
    auth()->attempt(['type' => 'phone', 'value' => $login->value, 'password' => 'shared-password']);
    $key = auth()->guard()->getName();
    $login->delete();
    app('auth')->forgetGuards();

    expect(session($key))->toBe($this->user->id)->and(auth()->id())->toBe($this->user->id);
});

it('keeps remember tokens with the user and revokes them after a password change', function () {
    Login::register($this->user, LoginType::Login, 'username');
    auth()->attempt(['type' => 'login', 'value' => 'username', 'password' => 'shared-password'], true);
    $provider = auth()->guard()->getProvider();
    $token = $this->user->fresh()->remember_token;
    expect($token)->toBeString()->not->toBeEmpty();
    expect($provider->retrieveByToken($this->user->id, $token)->id)->toBe($this->user->id);

    $this->user->update(['password' => 'changed-password']);
    expect($provider->retrieveByToken($this->user->id, $token))->toBeNull();
});

it('invalidates sessions after changing the shared password', function () {
    Route::get('/login', fn () => 'Login')->name('login');
    Route::middleware(['web', Authenticate::class, AuthenticateSession::class])
        ->get('/account', fn () => response()->json(['id' => auth()->id()]));
    auth()->login($this->user);
    $this->getJson('/account')->assertOk()->assertJsonPath('id', $this->user->id);
    $this->user->update(['password' => 'changed-password']);
    app('auth')->forgetGuards();

    $this->getJson('/account')->assertUnauthorized();
});

it('maintains a default for each login type and routes mail to the default email', function () {
    $first = Login::register($this->user, LoginType::Email, 'first@example.test');
    $second = Login::register($this->user, LoginType::Email, 'second@example.test');
    $phone = Login::register($this->user, LoginType::Phone, '+48123456789');

    expect($first->is_default)->toBeTrue()->and($second->is_default)->toBeFalse()->and($phone->is_default)->toBeTrue();
    expect($this->user->getEmailForPasswordReset())->toBe('first@example.test');
    $second->is_default = true;
    $second->save();
    expect($first->fresh()->is_default)->toBeFalse();
    expect($this->user->routeNotificationForMail())->toBe('second@example.test');
    $second->delete();
    expect($first->fresh()->is_default)->toBeTrue();
    $first->delete();
    expect($this->user->getEmailForPasswordReset())->toBeNull();
});

it('restores the original user after impersonation without requiring target logins', function () {
    auth()->login($this->user);
    $target = User::register('Target', ['admin']);
    $target->save();
    $manager = app(\Lab404\Impersonate\Services\ImpersonateManager::class);

    expect($manager->take(auth()->user(), $target, 'web'))->toBeTrue();
    app('auth')->forgetGuards();
    expect(auth()->id())->toBe($target->id);
    expect($manager->leave())->toBeTrue();
    app('auth')->forgetGuards();
    expect(auth()->id())->toBe($this->user->id);
});

it('does not restore impersonator access after their password changes', function () {
    auth()->login($this->user);
    $target = User::register('Target', ['admin']);
    $target->save();
    $manager = app(\Lab404\Impersonate\Services\ImpersonateManager::class);
    expect($manager->take(auth()->user(), $target, 'web'))->toBeTrue();
    $this->user->update(['password' => 'changed-password']);

    expect($manager->leave())->toBeTrue();
    app('auth')->forgetGuards();
    expect(auth()->guest())->toBeTrue();
});

it('leaves impersonation safely after the original user was deleted', function () {
    auth()->login($this->user);
    $target = User::register('Target', ['admin']);
    $target->save();
    $manager = app(\Lab404\Impersonate\Services\ImpersonateManager::class);
    expect($manager->take($this->user, $target, 'web'))->toBeTrue();
    $this->user->delete();

    expect(app(\PHPinnacle\Cerber\Auth\ImpersonationSession::class)->leave($manager))
        ->toBeFalse()
        ->and($manager->isImpersonating())
        ->toBeFalse()
        ->and(auth()->guest())
        ->toBeTrue();
});
