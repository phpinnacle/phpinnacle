<?php

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Database\UniqueConstraintViolationException;
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

it('authenticates local identifiers through username with one user password', function () {
    Login::register($this->user, LoginType::Email, 'first@example.test')->markVerified();
    Login::register($this->user, LoginType::Email, 'second@example.test')->markVerified();
    Login::register($this->user, LoginType::Phone, '+48123456789');
    Login::register($this->user, LoginType::Login, 'Username');

    foreach ([
        'FIRST@example.test',
        'second@example.test',
        '+48123456789',
        'Username',
    ] as $username) {
        expect(auth()->attempt(['username' => $username, 'password' => 'shared-password']))->toBeTrue();
        expect(auth()->user())->toBeInstanceOf(User::class)->and(auth()->id())->toBe($this->user->id);
        auth()->logout();
    }

    expect(auth()->attempt(['email' => 'first@example.test', 'password' => 'wrong-password']))->toBeFalse();
    expect($this->user->toArray())->not->toHaveKeys(['password', 'remember_token']);
});

it('rejects type and value credentials for a registered local login', function () {
    Login::register($this->user, LoginType::Phone, '+48123456789');

    expect(auth()->attempt([
        'type' => 'phone',
        'value' => '+48123456789',
        'password' => 'shared-password',
    ]))->toBeFalse();
});

it('limits lookup to the requested local login types', function () {
    $email = Login::register($this->user, LoginType::Email, 'first@example.test');
    $phone = Login::register($this->user, LoginType::Phone, '+48123456789');
    $login = Login::register($this->user, LoginType::Login, 'Username');

    expect(Login::lookup('FIRST@example.test', LoginType::Email, LoginType::Login)?->id)->toBe($email->id);
    expect(Login::lookup('+48123456789', LoginType::Email, LoginType::Phone)?->id)->toBe($phone->id);
    expect(Login::lookup('Username', LoginType::Email, LoginType::Phone))->toBeNull();
    expect(Login::lookup('Username', LoginType::Login)?->id)->toBe($login->id);
    expect(Login::lookup('username', LoginType::Login))->toBeNull();
});

it('resolves ambiguous values only when the requested types select one login', function () {
    $email = Login::register($this->user, LoginType::Email, 'same@example.test');
    $login = Login::register($this->user, LoginType::Login, 'SAME@example.test');

    expect(Login::lookup('SAME@example.test'))->toBeNull();
    expect(Login::lookup('SAME@example.test', LoginType::Email, LoginType::Login))->toBeNull();
    expect(Login::lookup('SAME@example.test', LoginType::Email)?->id)->toBe($email->id);
    expect(Login::lookup('SAME@example.test', LoginType::Login)?->id)->toBe($login->id);
});

it('enforces unique local values across login types and owners', function (bool $sameOwner) {
    Login::register($this->user, LoginType::Phone, '+48123456789');
    $owner = $sameOwner ? $this->user : User::register('Other', ['admin']);
    $owner->save();

    expect(fn () => Login::register($owner, LoginType::Login, '+48123456789'))
        ->toThrow(UniqueConstraintViolationException::class);
})->with([true, false]);

it('keeps differently cased local login values distinct', function () {
    Login::register($this->user, LoginType::Login, 'Username');
    $other = User::register('Other', ['admin']);
    $other->password = 'other-password';
    $other->save();
    Login::register($other, LoginType::Login, 'username');

    expect(auth()->attempt(['username' => 'username', 'password' => 'shared-password']))->toBeFalse();
    expect(auth()->attempt(['username' => 'Username', 'password' => 'shared-password']))->toBeTrue();
    expect(auth()->id())->toBe($this->user->id);
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
    auth()->attempt(['username' => $login->value, 'password' => 'shared-password']);
    $key = auth()->guard()->getName();
    $login->delete();
    app('auth')->forgetGuards();

    expect(session($key))->toBe($this->user->id)->and(auth()->id())->toBe($this->user->id);
});

it('keeps remember tokens with the user and revokes them after a password change', function () {
    Login::register($this->user, LoginType::Login, 'username');
    auth()->attempt(['username' => 'username', 'password' => 'shared-password'], true);
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

it('clears the temporary password marker after changing the password', function () {
    $this->user->update(['is_password_temporary' => true]);

    $this->user->update(['password' => 'changed-password']);

    expect($this->user->fresh()->is_password_temporary)->toBeFalse();
    expect($this->user->fresh()->prerequisites())->toBe([]);
});

it('preserves the temporary password marker when updating other user details', function () {
    $this->user->update(['is_password_temporary' => true]);

    $this->user->update(['name' => 'Updated user']);

    expect($this->user->fresh()->is_password_temporary)->toBeTrue();
});

it('allows a newly assigned password to be marked temporary', function () {
    $this->user->update(['password' => 'temporary-password', 'is_password_temporary' => true]);

    expect($this->user->fresh()->is_password_temporary)->toBeTrue();
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

    expect(app(\PHPinnacle\Cerber\Services\ImpersonationSession::class)->leave($manager))
        ->toBeFalse()
        ->and($manager->isImpersonating())
        ->toBeFalse()
        ->and(auth()->guest())
        ->toBeTrue();
});
