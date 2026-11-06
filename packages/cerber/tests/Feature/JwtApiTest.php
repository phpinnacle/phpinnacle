<?php

namespace CerberJwtApiTests;

use Filament\Auth\Notifications\ResetPassword;
use Filament\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use PHPinnacle\Cerber\Enums\LoginType;
use PHPinnacle\Cerber\Enums\UserStatus;
use PHPinnacle\Cerber\Models\Login;
use PHPinnacle\Cerber\Models\Tenant;
use PHPinnacle\Cerber\Models\User as BaseUser;
use Tests\TestCase;

class User extends BaseUser {}

class JwtApiTestCase extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set([
            'database.connections.cerber' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'foreign_key_constraints' => true,
            ],
            'phpinnacle-cerber.connection' => 'cerber',
            'phpinnacle-cerber.user_model' => User::class,
            'phpinnacle-cerber.api.enabled' => true,
            'phpinnacle-cerber.api.frontend_url' => 'https://frontend.example',
            'auth.providers.users' => ['driver' => 'cerber', 'model' => User::class],
            'auth.guards.api' => ['driver' => 'jwt', 'provider' => 'users'],
            'auth.defaults.passwords' => 'users',
            'auth.passwords.users' => [
                'provider' => 'users',
                'table' => 'password_reset_tokens',
                'connection' => 'cerber',
                'expire' => 60,
                'throttle' => 60,
            ],
            'auth.timebox_duration' => 0,
            'cache.default' => 'array',
            'jwt.secret' => str_repeat('s', 64),
            'jwt.blacklist_enabled' => true,
            'jwt.blacklist_grace_period' => 0,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->artisan('migrate')->assertSuccessful();
        Tenant::default('default');
    }

    public function apiUser(bool $verified = true): User
    {
        $user = User::register('Customer', ['web']);
        $user->password = 'original-password';
        $user->save();
        $login = Login::register($user, LoginType::Email, 'customer@example.test');

        if ($verified) {
            $login->markVerified();
        }

        return $user;
    }

    public function forgetJwt(): void
    {
        app('auth')->forgetGuards();
        app('tymon.jwt')->unsetToken();
    }
}

uses(JwtApiTestCase::class);

it('registers the configured user and email login and sends a verifiable frontend link', function () {
    Notification::fake();
    $this->freezeTime();

    $response = $this
        ->postJson('/api/auth/register', [
            'email' => 'NEW@example.test',
            'password' => 'new-password',
        ])
        ->assertCreated()
        ->assertJsonPath('user.email', 'new@example.test')
        ->assertJsonPath('user.email_verified_at', null)
        ->assertJsonPath('prerequisites', [])
        ->assertJsonMissingPath('actions');
    $user = User::query()->findOrFail($response->json('user.id'));
    $login = $user->defaultLogin(LoginType::Email);

    expect($user->name)->toBe('new')->and($user->panels)->toBe(['web']);
    expect(Hash::check('new-password', $user->password))->toBeTrue();
    expect($response->json())->toHaveKeys(['access_token', 'token_type', 'expires_in', 'prerequisites', 'user']);
    expect($response->json('user'))
        ->toHaveCount(6)
        ->toHaveKeys(['id', 'name', 'status', 'email', 'email_verified_at', 'created_at']);

    $notification = Notification::sent(new AnonymousNotifiable, VerifyEmail::class)->sole();
    Notification::assertSentOnDemand(
        VerifyEmail::class,
        fn ($notification, $channels, AnonymousNotifiable $notifiable) => (
            $notifiable->routes['mail'] === 'new@example.test'
        ),
    );
    expect($notification->url)->toStartWith('https://frontend.example/auth/verify-email?');
    parse_str(parse_url($notification->url, PHP_URL_QUERY), $query);
    expect($query['id'])->toBe($login->id);
    $this
        ->getJson('/api/auth/verify-email?' . http_build_query($query))
        ->assertOk()
        ->assertJsonPath('email', 'new@example.test');
    expect($login->fresh()->verified_at)->not->toBeNull();
    $this->forgetJwt();
    $this
        ->withToken($response->json('access_token'))
        ->getJson('/api/profile')
        ->assertOk()
        ->assertJsonPath('id', $user->id);
});

it('resends a verifiable frontend link to the requested email without signing in', function () {
    Notification::fake();
    $user = $this->apiUser(false);
    $login = Login::register($user, LoginType::Email, 'secondary@example.test');

    $this
        ->postJson('/api/auth/verify-email/resend', ['email' => 'SECONDARY@example.test'])
        ->assertOk()
        ->assertExactJson(['message' => 'If this email login needs verification, a message has been sent.']);

    $this->assertGuest('api');
    expect($login->fresh()->verified_at)->toBeNull();
    Notification::assertSentOnDemand(
        VerifyEmail::class,
        fn ($notification, $channels, AnonymousNotifiable $notifiable) => (
            $notifiable->routes['mail'] === 'secondary@example.test'
        ),
    );
    $notification = Notification::sent(new AnonymousNotifiable, VerifyEmail::class)->sole();
    expect($notification->url)->toStartWith('https://frontend.example/auth/verify-email?');
    $this->getJson('/api/auth/verify-email?' . parse_url($notification->url, PHP_URL_QUERY))->assertOk();
    expect($login->fresh()->verified_at)->not->toBeNull();
    expect($user->defaultLogin(LoginType::Email)->verified_at)->toBeNull();
});

it('does not reveal missing verified or unavailable accounts when resending verification', function (string $case) {
    Notification::fake();
    $user = $this->apiUser($case === 'verified');

    if (in_array($case, ['blocked', 'archived'], true)) {
        $user->update(['status' => UserStatus::from($case)]);
    }

    $this
        ->postJson('/api/auth/verify-email/resend', [
            'email' => $case === 'unknown' ? 'missing@example.test' : 'customer@example.test',
        ])
        ->assertOk()
        ->assertExactJson(['message' => 'If this email login needs verification, a message has been sent.']);

    Notification::assertNothingSent();
})->with(['unknown', 'verified', 'blocked', 'archived']);

it('rejects invalid verification resend input with 422 without sending mail', function (array $data) {
    Notification::fake();

    $this
        ->postJson('/api/auth/verify-email/resend', $data)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');

    Notification::assertNothingSent();
})->with([
    'missing email' => [[]],
    'email array' => [['email' => ['customer@example.test']]],
    'invalid email' => [['email' => 'invalid']],
    'email too long' => [['email' => str_repeat('a', 245) . '@example.test']],
]);

it('limits verification resend to five requests per hour per IP with 429', function () {
    Notification::fake();
    $this->freezeTime();
    $this->apiUser(false);
    $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.1']);

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->postJson('/api/auth/verify-email/resend', ['email' => 'customer@example.test'])->assertOk();
    }

    $this->postJson('/api/auth/verify-email/resend', ['email' => 'different@example.test'])->assertTooManyRequests();
    $this->postJson('/api/auth/login', ['username' => 'missing@example.test', 'password' => 'wrong-password'])
        ->assertUnauthorized();

    $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.2']);
    $this->postJson('/api/auth/verify-email/resend', ['email' => 'customer@example.test'])->assertOk();

    $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.1']);
    $this->travel(59)->minutes();
    $this->postJson('/api/auth/verify-email/resend', ['email' => 'customer@example.test'])->assertTooManyRequests();

    $this->travel(1)->minutes();
    $this->postJson('/api/auth/verify-email/resend', ['email' => 'customer@example.test'])->assertOk();

    Notification::assertSentOnDemandTimes(VerifyEmail::class, 7);
});

it('rejects duplicate canonical email logins with 422 without creating another user', function () {
    $this->apiUser();

    $this
        ->postJson('/api/auth/register', [
            'email' => 'CUSTOMER@example.test',
            'password' => 'new-password',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');

    expect(User::query()->count())->toBe(1);
});

it('validates registration input with 422 before creating users', function (array $data, array $errors) {
    $this->postJson('/api/auth/register', $data)->assertUnprocessable()->assertJsonValidationErrors($errors);

    expect(User::query()->count())->toBe(0);
})->with([
    'missing credentials' => [[], ['email', 'password']],
    'email array' => [
        ['email' => ['invalid'], 'password' => 'long-password'],
        ['email'],
    ],
    'invalid email and short password' => [
        ['email' => 'invalid', 'password' => 'short'],
        ['email', 'password'],
    ],
]);

it('authenticates a verified email with the shared password and returns the default email profile', function (
    bool $temporary,
    array $prerequisites,
) {
    $user = $this->apiUser();
    $user->update(['is_password_temporary' => $temporary]);
    Login::register($user, LoginType::Email, 'secondary@example.test')->markVerified();

    $response = $this
        ->postJson('/api/auth/login', ['username' => 'SECONDARY@example.test', 'password' => 'original-password'])
        ->assertOk()
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.email', 'customer@example.test')
        ->assertJsonPath('token_type', 'bearer')
        ->assertJsonPath('prerequisites', $prerequisites)
        ->assertJsonPath('expires_in', 3600);
    $this->forgetJwt();
    $this
        ->withToken($response->json('access_token'))
        ->getJson('/api/profile')
        ->assertOk()
        ->assertJsonPath('id', $user->id)
        ->assertJsonMissingPath('password');
})->with([
    'regular' => [false, []],
    'temporary' => [true, ['password']],
]);

it('rejects an unverified email with 403 after checking the shared password', function () {
    $this->apiUser(false);

    $this
        ->postJson('/api/auth/login', [
            'username' => 'CUSTOMER@example.test',
            'password' => 'original-password',
        ])
        ->assertForbidden()
        ->assertExactJson(['message' => 'Email is not verified.']);

    $this->assertGuest('api');
});

it('rejects unavailable users and invalid credentials with 401', function (string $case, bool $verified) {
    $user = $this->apiUser($verified);

    if (in_array($case, ['blocked', 'archived'], true)) {
        $user->update(['status' => UserStatus::from($case)]);
    }

    $this
        ->postJson('/api/auth/login', [
            'username' => $case === 'unknown' ? 'missing@example.test' : 'customer@example.test',
            'password' => $case === 'incorrect' ? 'wrong-password' : 'original-password',
        ])
        ->assertUnauthorized()
        ->assertJsonPath('message', 'Unauthenticated.');
})->with([
    'blocked' => ['blocked', true],
    'archived' => ['archived', true],
    'unknown' => ['unknown', true],
    'incorrect' => ['incorrect', true],
    'unverified with incorrect password' => ['incorrect', false],
    'unverified and blocked' => ['blocked', false],
    'unverified and archived' => ['archived', false],
]);

it('authenticates phone numbers and case-sensitive logins through username', function (LoginType $type, string $value) {
    $user = $this->apiUser(false);
    Login::register($user, $type, $value);

    $this
        ->postJson('/api/auth/login', ['username' => $value, 'password' => 'original-password'])
        ->assertOk()
        ->assertJsonPath('user.id', $user->id);

    if ($type === LoginType::Login) {
        $this->forgetJwt();
        $this->postJson('/api/auth/login', ['username' => 'customer', 'password' => 'original-password'])
            ->assertUnauthorized();
    }
})->with([
    'phone' => [LoginType::Phone, '+48123456789'],
    'login' => [LoginType::Login, 'Customer'],
]);

it('rejects ambiguous usernames matching both an email and a case-sensitive login with 401', function (
    #[\SensitiveParameter]
    string $password,
) {
    $this->apiUser();
    $other = User::register('Other', ['web']);
    $other->password = 'other-password';
    $other->save();
    Login::register($other, LoginType::Login, 'CUSTOMER@example.test');

    $this->postJson('/api/auth/login', [
        'username' => 'CUSTOMER@example.test',
        'password' => $password,
    ])->assertUnauthorized();
})->with(['original-password', 'other-password']);

it('validates login input with 422', function (array $data, array $errors) {
    $this
        ->postJson('/api/auth/login', $data)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($errors);
})->with([
    'missing credentials' => [[], ['username', 'password']],
    'username array' => [['username' => ['invalid']], ['username', 'password']],
    'username too long' => [['username' => str_repeat('a', 256), 'password' => 'password'], ['username']],
    'email without username' => [['email' => 'customer@example.test', 'password' => 'password'], ['username']],
]);

it('rejects registration emails already used as local usernames with 422', function () {
    $user = $this->apiUser();
    Login::register($user, LoginType::Login, 'taken@example.test');

    $this
        ->postJson('/api/auth/register', [
            'email' => 'TAKEN@example.test',
            'password' => 'new-password',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');

    expect(User::query()->count())->toBe(1);
});

it('rejects unauthenticated protected requests with 401', function (string $method, string $url) {
    $this->{$method}($url)->assertUnauthorized();
})->with([
    ['getJson',  '/api/profile'],
    ['postJson', '/api/auth/refresh'],
    ['postJson', '/api/auth/logout'],
    ['postJson', '/api/auth/password/change'],
]);

it('rotates JWTs and rejects both refreshed and logged out tokens', function () {
    $user = $this->apiUser();
    $token = auth('api')->login($user);
    $this->forgetJwt();
    $this->travel(1)->seconds();

    $response = $this
        ->withToken($token)
        ->postJson('/api/auth/refresh')
        ->assertOk()
        ->assertJsonPath('user.id', $user->id);
    $replacement = $response->json('access_token');
    expect($replacement)->not->toBe($token);
    $this->forgetJwt();
    $this->withToken($token)->getJson('/api/profile')->assertUnauthorized();
    $this->forgetJwt();
    $this->withToken($replacement)->postJson('/api/auth/logout')->assertNoContent();
    $this->forgetJwt();
    $this->withToken($replacement)->getJson('/api/profile')->assertUnauthorized();
});

it('rejects expired tokens and users blocked after issuance with 401', function (bool $expired) {
    $user = $this->apiUser();
    $token = auth('api')->login($user);
    $this->forgetJwt();

    if ($expired) {
        $this->travel(61)->minutes();
    } else {
        $user->update(['status' => UserStatus::Blocked]);
    }

    $this->withToken($token)->getJson('/api/profile')->assertUnauthorized();
})->with([true, false]);

it('rejects tampered expired and changed-email verification links with 403', function (string $case) {
    Notification::fake();
    $this->postJson('/api/auth/register', [
        'email' => 'new@example.test',
        'password' => 'new-password',
    ])->assertCreated();
    $login = Login::lookup('new@example.test', LoginType::Email);
    $notification = Notification::sent(new AnonymousNotifiable, VerifyEmail::class)->sole();
    $query = parse_url($notification->url, PHP_URL_QUERY);

    if ($case === 'tampered') {
        $query .= '&extra=changed';
    } elseif ($case === 'expired') {
        $this->travel(61)->minutes();
    } else {
        $login->update(['value' => 'changed@example.test']);
    }

    $this->getJson('/api/auth/verify-email?' . $query)->assertForbidden();
    expect($login->fresh()->verified_at)->toBeNull();
})->with(['tampered', 'expired', 'changed email']);

it('recovers the shared password through a secondary email and consumes the token', function () {
    Notification::fake();
    $user = $this->apiUser(false);
    Login::register($user, LoginType::Email, 'secondary@example.test');
    $this->postJson('/api/auth/password/forgot', ['email' => 'SECONDARY@example.test'])->assertOk();
    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) {
        expect($notification->url)->toStartWith('https://frontend.example/auth/reset-password?');
        parse_str(parse_url($notification->url, PHP_URL_QUERY), $query);
        expect($query['email'])->toBe('customer@example.test');

        return true;
    });
    $token = Notification::sent($user, ResetPassword::class)->sole()->token;
    $data = [
        'email' => 'secondary@example.test',
        'token' => $token,
        'password' => 'replacement-password',
    ];

    $this->postJson('/api/auth/password/reset', $data)->assertOk();
    expect(Hash::check('replacement-password', $user->fresh()->password))->toBeTrue();
    expect($user->fresh()->remember_token)->not->toBeNull();
    expect($user->defaultLogin(LoginType::Email)->verified_at)->toBeNull();
    $this->postJson('/api/auth/password/reset', $data)->assertUnprocessable()->assertJsonValidationErrors('token');
});

it('does not reveal missing accounts or resend throttled password mail', function () {
    Notification::fake();
    $user = $this->apiUser();
    $known = $this->postJson('/api/auth/password/forgot', ['email' => 'customer@example.test'])->assertOk();
    $missing = $this->postJson('/api/auth/password/forgot', ['email' => 'missing@example.test'])->assertOk();
    $throttled = $this->postJson('/api/auth/password/forgot', ['email' => 'customer@example.test'])->assertOk();

    expect($missing->json())->toBe($known->json())->and($throttled->json())->toBe($known->json());
    Notification::assertSentToTimes($user, ResetPassword::class, 1);
});

it('rejects invalid reset credentials with 422 without changing the password', function () {
    $user = $this->apiUser();
    $password = $user->password;

    $this
        ->postJson('/api/auth/password/reset', [
            'email' => 'customer@example.test',
            'token' => 'invalid',
            'password' => 'replacement-password',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('token');

    expect($user->fresh()->password)->toBe($password);
});

it('rejects expired reset tokens with 422 without changing the password', function () {
    Notification::fake();
    $user = $this->apiUser();
    $password = $user->password;
    $this->postJson('/api/auth/password/forgot', ['email' => 'customer@example.test'])->assertOk();
    $token = Notification::sent($user, ResetPassword::class)->sole()->token;
    $this->travel(61)->minutes();

    $this
        ->postJson('/api/auth/password/reset', [
            'email' => 'customer@example.test',
            'token' => $token,
            'password' => 'replacement-password',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('token');

    expect($user->fresh()->password)->toBe($password);
});

it('validates password recovery input with 422', function () {
    $this
        ->postJson('/api/auth/password/forgot', ['email' => 'invalid'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');
    $this
        ->postJson('/api/auth/password/reset', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'token', 'password']);
});

it('limits repeated password sign-in attempts with 429', function () {
    for ($attempt = 0; $attempt < 6; $attempt++) {
        $this->postJson('/api/auth/login', ['username' => 'missing@example.test', 'password' => 'wrong-password'])
            ->assertUnauthorized();
    }

    $this->postJson('/api/auth/login', ['username' => 'missing@example.test', 'password' => 'wrong-password'])
        ->assertTooManyRequests();
});

it('rejects invalid bearer tokens with 401', function () {
    $this->withToken('invalid-token')->getJson('/api/profile')->assertUnauthorized();
});

it('changes the authenticated user password and preserves the bearer session', function () {
    $user = $this->apiUser();
    $user->setRememberToken('original-remember-token');
    $user->save();
    $token = auth('api')->login($user);
    $this->forgetJwt();

    $this
        ->withToken($token)
        ->postJson('/api/auth/password/change', [
            'current' => 'original-password',
            'password' => 'changed-password',
        ])
        ->assertNoContent();

    expect(Hash::check('changed-password', $user->fresh()->password))->toBeTrue();
    expect($user->fresh()->getRememberToken())->not->toBe('original-remember-token');
    $this->forgetJwt();
    $this->withToken($token)->getJson('/api/profile')->assertOk()->assertJsonPath('id', $user->id);
    $this->forgetJwt();
    $this->postJson('/api/auth/login', ['username' => 'customer@example.test', 'password' => 'original-password'])
        ->assertUnauthorized();
    $this
        ->postJson('/api/auth/login', ['username' => 'customer@example.test', 'password' => 'changed-password'])
        ->assertOk()
        ->assertJsonPath('user.id', $user->id);
});

it('lets authenticated passwordless users set a first password and requires it thereafter', function () {
    $user = $this->apiUser();
    $user->update(['password' => null]);
    $token = auth('api')->login($user);
    $this->forgetJwt();

    $this
        ->withToken($token)
        ->postJson('/api/auth/password/change', [
            'password' => 'first-password',
        ])
        ->assertNoContent();

    expect(Hash::check('first-password', $user->fresh()->password))->toBeTrue();
    $this->forgetJwt();
    $this
        ->withToken($token)
        ->postJson('/api/auth/password/change', [
            'password' => 'second-password',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('current');
    expect(Hash::check('first-password', $user->fresh()->password))->toBeTrue();
});

it('rejects password changes with invalid current credentials with 422', function (
    #[\SensitiveParameter]
    mixed $currentPassword,
) {
    $user = $this->apiUser();
    $password = $user->password;
    $rememberToken = $user->getRememberToken();
    $token = auth('api')->login($user);
    $this->forgetJwt();

    $this
        ->withToken($token)
        ->postJson('/api/auth/password/change', [
            'current' => $currentPassword,
            'password' => 'changed-password',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('current');

    expect($user->fresh()->password)->toBe($password);
    expect($user->fresh()->getRememberToken())->toBe($rememberToken);
})->with(['missing' => [null], 'incorrect' => ['incorrect'], 'array' => [['invalid']]]);

it('rejects password changes with invalid new credentials with 422', function (array $data) {
    $user = $this->apiUser();
    $password = $user->password;
    $token = auth('api')->login($user);
    $this->forgetJwt();

    $this
        ->withToken($token)
        ->postJson('/api/auth/password/change', [
            ...$data,
            'current' => 'original-password',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('password');

    expect($user->fresh()->password)->toBe($password);
})->with([
    'missing' => [[]],
    'short' => [['password' => 'short']],
]);

it('limits repeated password change attempts with 429', function () {
    $user = $this->apiUser();
    $token = auth('api')->login($user);
    $this->forgetJwt();

    for ($attempt = 0; $attempt < 6; $attempt++) {
        $this
            ->withToken($token)
            ->postJson('/api/auth/password/change', [
                'current' => 'incorrect',
                'password' => 'changed-password',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('current');
    }

    $this->withToken($token)->postJson('/api/auth/password/change', [])->assertTooManyRequests();
});
