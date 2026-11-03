<?php

use Filament\Actions\Testing\TestAction;
use Filament\Auth\Notifications\VerifyEmail;
use Filament\Auth\Pages\Login as LoginPage;
use Filament\Auth\Pages\PasswordReset\ResetPassword;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\PanelRegistry;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use PHPinnacle\Cerber\CerberPlugin;
use PHPinnacle\Cerber\Enums\LoginType;
use PHPinnacle\Cerber\Models\Login;
use PHPinnacle\Cerber\Models\Role;
use PHPinnacle\Cerber\Models\Tenant;
use PHPinnacle\Cerber\Models\User;
use PHPinnacle\Cerber\Pages\EditProfile;
use PHPinnacle\Cerber\Resources\Users\Pages\CreateUser;
use PHPinnacle\Cerber\Resources\Users\Pages\EditUser;
use PHPinnacle\Cerber\Services\UserService;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Notification::fake();
    config([
        'auth.guards.web' => ['driver' => 'session', 'provider' => 'users'],
        'auth.providers.users' => ['driver' => 'cerber', 'model' => \PHPinnacle\Cerber\Models\User::class],
        'auth.passwords.users.provider' => 'users',
    ]);
    $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    $this->artisan('migrate')->assertSuccessful();
    $this->tenant = Tenant::default('default');
    $panel = Panel::make()
        ->id('admin')
        ->path('admin')
        ->default()
        ->middleware([SubstituteBindings::class])
        ->plugin(CerberPlugin::make()->withoutProviders());
    app(PanelRegistry::class)->register($panel);
    Filament::setCurrentPanel($panel);
    require base_path('vendor/filament/filament/routes/web.php');
    Route::getRoutes()->refreshNameLookups();

    $this->user = User::register('User', ['admin']);
    $this->user->password = 'password';
    $this->user->save();
    $this->actingAs($this->user);
});

it('renders the profile without an MFA section when the panel disables MFA', function () {
    Filament::getCurrentOrDefaultPanel()->multiFactorAuthentication([]);

    Livewire::test(EditProfile::class)
        ->assertOk()
        ->assertSee('Logins')
        ->assertDontSee('Two-Factor Authentication')
        ->call('$refresh')
        ->assertOk()
        ->assertDontSee('Two-Factor Authentication');
});

it('creates a user and adds several typed logins through independent dialogs', function () {
    $this->user->grant(Role::register($this->tenant, 'Administrator', ['create_user', 'view_any_user', 'update_user']));

    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'New user', 'password' => 'new-user-password', 'is_password_temporary' => true])
        ->call('create')
        ->assertHasNoFormErrors();
    $user = User::query()->where('name', 'New user')->sole();
    expect($user->is_password_temporary)->toBeTrue();
    $page = Livewire::test(EditUser::class, ['record' => $user->id])
        ->assertFormSet(['is_password_temporary' => true]);
    foreach ([
        ['email', 'FIRST@example.test'],
        ['email', 'second@example.test'],
        ['phone', '+48123456789'],
        ['login', 'username'],
    ] as [$type, $value]) {
        $page->callAction(
            TestAction::make('add')->schemaComponent('logins.' . $type . '.add', 'form'),
            compact('value'),
        )->assertHasNoErrors();
    }

    expect($user->logins()->count())->toBe(4);
    expect(Hash::check('new-user-password', $user->password))->toBeTrue();
    $page->fillForm(['is_password_temporary' => false])->call('save')->assertHasNoFormErrors();
    expect($user->fresh()->is_password_temporary)->toBeFalse();
    Notification::assertSentOnDemand(
        VerifyEmail::class,
        fn ($notification, $channels, AnonymousNotifiable $notifiable) => (
            $notifiable->routes['mail'] === 'first@example.test'
        ),
    );
    Notification::assertSentOnDemand(
        VerifyEmail::class,
        fn ($notification, $channels, AnonymousNotifiable $notifiable) => (
            $notifiable->routes['mail'] === 'second@example.test'
        ),
    );
});

it('rejects local values already owned by another user across types', function (
    LoginType $existingType,
    string $type,
    string $value,
) {
    $this->user->grant(Role::register($this->tenant, 'Editor', ['view_any_user', 'update_user']));
    Login::register($this->user, $existingType, $value);
    $other = User::register('Other');
    $other->save();
    Livewire::test(EditUser::class, ['record' => $other->id])
        ->callAction(TestAction::make('add')->schemaComponent('logins.' . $type . '.add', 'form'), [
            'value' => $type === 'email' ? 'DUPLICATE@example.test' : $value,
        ])
        ->assertHasActionErrors(['value' => 'unique']);
    expect($other->logins()->count())->toBe(0);
    Notification::assertNothingSent();
})->with([
    'canonical email' => [LoginType::Email, 'email', 'duplicate@example.test'],
    'email used as login' => [LoginType::Email, 'login', 'duplicate@example.test'],
    'login used as email' => [LoginType::Login, 'email', 'duplicate@example.test'],
    'phone used as login' => [LoginType::Phone, 'login', '+48123456789'],
]);

it('rejects duplicate local values when editing and allows the current value', function () {
    Login::register($this->user, LoginType::Phone, '+48123456789');
    $login = Login::register($this->user, LoginType::Login, 'Username');
    $action = TestAction::make('edit')->schemaComponent('logins.login.' . $login->id, 'content');

    Livewire::test(EditProfile::class)
        ->callAction($action, ['value' => '+48123456789', 'is_default' => true])
        ->assertHasActionErrors(['value' => 'unique']);
    expect($login->fresh()->value)->toBe('Username');

    Livewire::test(EditProfile::class)
        ->callAction($action, ['value' => 'Username', 'is_default' => true])
        ->assertHasNoErrors();
    expect($login->fresh()->value)->toBe('Username');
});

it('validates login values in the creation dialog', function (string $type, string $value) {
    Livewire::test(EditProfile::class)
        ->callAction(TestAction::make('add')->schemaComponent('logins.' . $type . '.add', 'content'), compact('value'))
        ->assertHasActionErrors(['value']);
    $this->assertDatabaseCount('logins', 0);
})->with([
    ['email', 'not-an-email'],
    ['phone', '12345'],
]);

it('edits a login immediately without changing its type or saving the main user form', function () {
    $this->user->grant(Role::register($this->tenant, 'Editor', ['view_any_user', 'update_user']));
    $previous = Login::register($this->user, LoginType::Email, 'default@example.test');
    $login = Login::register($this->user, LoginType::Email, 'first@example.test');
    $login->markVerified();
    $page = Livewire::test(EditUser::class, ['record' => $this->user->id])->fillForm(['name' => 'Unsaved name']);
    $page
        ->callAction(TestAction::make('edit')->schemaComponent('logins.email.' . $login->id, 'form'), [
            'value' => 'changed@example.test',
            'type' => 'phone',
            'is_default' => true,
        ])
        ->assertHasNoErrors()
        ->assertFormSet(['name' => 'Unsaved name']);
    expect($this->user->fresh()->name)->toBe('User');
    expect($login->fresh()->value)->toBe('changed@example.test');
    expect($login->fresh()->type)->toBe(LoginType::Email);
    expect($login->fresh()->verified_at)->toBeNull();
    expect($login->fresh()->is_default)->toBeTrue()->and($previous->fresh()->is_default)->toBeFalse();
    expect(Hash::check('password', $this->user->fresh()->password))->toBeTrue();
    Notification::assertSentOnDemand(
        VerifyEmail::class,
        fn ($notification, $channels, AnonymousNotifiable $notifiable) => (
            $notifiable->routes['mail'] === 'changed@example.test'
        ),
    );
});

it('preserves profile drafts while adding logins and saves profile details separately', function () {
    $page = Livewire::test(EditProfile::class)->fillForm(['name' => 'Updated profile']);
    $page->callAction(TestAction::make('add')->schemaComponent('logins.phone.add', 'content'), [
        'value' => '+48123456789',
    ])->assertHasNoErrors()->assertFormSet(['name' => 'Updated profile']);
    expect($this->user->fresh()->name)->toBe('User');
    expect(Login::lookup('+48123456789', LoginType::Phone)->user_id)->toBe($this->user->id);
    $page->call('save')->assertHasNoFormErrors();
    expect($this->user->fresh()->name)->toBe('Updated profile');
    expect($this->user->logins()->count())->toBe(1);
});

it('changes the shared password from the profile', function () {
    Livewire::test(EditProfile::class)
        ->assertSee('Update your password')
        ->assertFormFieldDoesNotExist('is_password_temporary')
        ->fillForm([
            'password' => 'changed-password',
            'passwordConfirmation' => 'changed-password',
            'currentPassword' => 'password',
        ])
        ->call('save')
        ->assertHasNoFormErrors();
    expect(Hash::check('changed-password', $this->user->fresh()->password))->toBeTrue();
    expect(Hash::check('changed-password', auth()->user()->getAuthPassword()))->toBeTrue();
});

it('rejects profile password changes without the correct current password', function (
    #[\SensitiveParameter]
    ?string $currentPassword,
    string $rule,
) {
    $password = $this->user->password;
    $rememberToken = $this->user->getRememberToken();

    Livewire::test(EditProfile::class)
        ->fillForm([
            'password' => 'changed-password',
            'passwordConfirmation' => 'changed-password',
            'currentPassword' => $currentPassword,
        ])
        ->call('save')
        ->assertHasFormErrors(['currentPassword' => $rule]);

    expect($this->user->fresh()->password)->toBe($password);
    expect($this->user->fresh()->getRememberToken())->toBe($rememberToken);
})->with(['missing' => [null, 'required'], 'incorrect' => ['wrong-password', 'current_password']]);

it('lets a passwordless user set a first password and requires it for subsequent changes', function () {
    $this->user->update(['password' => null]);

    $page = Livewire::test(EditProfile::class)
        ->fillForm(['password' => 'first-password', 'passwordConfirmation' => 'first-password'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Hash::check('first-password', $this->user->fresh()->password))->toBeTrue();

    $page
        ->fillForm(['password' => 'second-password', 'passwordConfirmation' => 'second-password'])
        ->call('save')
        ->assertHasFormErrors(['currentPassword' => 'required']);

    expect(Hash::check('first-password', $this->user->fresh()->password))->toBeTrue();
});

it('deletes a profile login without ending the user session', function () {
    $login = Login::register($this->user, LoginType::Login, 'username');
    Livewire::test(EditProfile::class)
        ->callAction(TestAction::make('delete')->schemaComponent('logins.login.' . $login->id, 'content'))
        ->assertHasNoErrors();
    expect($login->fresh())->toBeNull();
    expect(auth()->id())->toBe($this->user->id);
});

it('limits manual verification to the user card and cannot resolve another owners login action', function () {
    $this->user->grant(Role::register($this->tenant, 'Editor', ['view_any_user', 'update_user']));
    $login = Login::register($this->user, LoginType::Email, 'first@example.test');
    $other = User::register('Other');
    $other->save();
    $foreign = Login::register($other, LoginType::Email, 'other@example.test');
    Livewire::test(EditProfile::class)
        ->assertActionDoesNotExist(TestAction::make('verify')->schemaComponent('logins.email.' . $login->id, 'content'))
        ->assertActionDoesNotExist(TestAction::make('dropVerification')->schemaComponent(
            'logins.email.' . $login->id,
            'content',
        ))
        ->assertActionDoesNotExist(TestAction::make('resendVerification')->schemaComponent(
            'logins.email.' . $login->id,
            'content',
        ))
        ->assertActionDoesNotExist(TestAction::make('resetPassword')->schemaComponent(
            'logins.email.' . $login->id,
            'content',
        ))
        ->assertActionDoesNotExist(TestAction::make('delete')->schemaComponent(
            'logins.email.' . $foreign->id,
            'content',
        ));
    Livewire::test(EditUser::class, ['record' => $this->user->id])
        ->assertActionDoesNotExist(TestAction::make('verify')->schemaComponent('logins.email.' . $foreign->id, 'form'))
        ->callAction(TestAction::make('verify')->schemaComponent('logins.email.' . $login->id, 'form'))
        ->assertHasNoErrors();
    expect($login->fresh()->verified_at)->not->toBeNull();
    expect($foreign->fresh()->verified_at)->toBeNull();
    Livewire::test(EditUser::class, ['record' => $this->user->id])
        ->callAction(TestAction::make('dropVerification')->schemaComponent('logins.email.' . $login->id, 'form'))
        ->assertHasNoErrors();
    expect($login->fresh()->verified_at)->toBeNull();
    Livewire::test(EditUser::class, ['record' => $this->user->id])
        ->callAction(TestAction::make('resendVerification')->schemaComponent('logins.email.' . $login->id, 'form'))
        ->assertHasNoErrors()
        ->callAction(TestAction::make('resetPassword')->schemaComponent('logins.email.' . $login->id, 'form'))
        ->assertHasNoErrors();
    Notification::assertSentOnDemand(
        VerifyEmail::class,
        fn ($notification, $channels, AnonymousNotifiable $notifiable) => (
            $notifiable->routes['mail'] === 'first@example.test'
        ),
    );
    Notification::assertSentTo($this->user, \Filament\Auth\Notifications\ResetPassword::class);
});

it('denies access to the user card without the update permission even for its owner', function () {
    $this->user->grant(Role::register($this->tenant, 'Reader', ['view_any_user', 'view_user']));

    Livewire::test(EditUser::class, ['record' => $this->user->id])->assertForbidden();
});

it('verifies only the email address bound to the signed verification link', function () {
    $login = Login::register($this->user, LoginType::Email, 'first@example.test');
    app(UserService::class)->resendVerification($login);
    $url = Notification::sent(new AnonymousNotifiable, VerifyEmail::class)->sole()->url;

    $this->get($url)->assertRedirect();
    expect($login->fresh()->verified_at)->not->toBeNull();

    $login->update(['value' => 'changed@example.test']);
    $this->get($url)->assertForbidden();
    expect($login->fresh()->verified_at)->toBeNull();
});

it('rejects expired and unsigned verification requests', function () {
    $login = Login::register($this->user, LoginType::Email, 'first@example.test');
    app(UserService::class)->resendVerification($login);
    $url = Notification::sent(new AnonymousNotifiable, VerifyEmail::class)->sole()->url;
    $this->get('/admin/logins/' . $login->id . '/verify?hash=' . hash('sha256', $login->value))->assertForbidden();
    $this->travel(61)->minutes();

    $this->get($url)->assertForbidden();
    expect($login->fresh()->verified_at)->toBeNull();
});

it('resets the shared user password through the standard page', function () {
    $login = Login::register($this->user, LoginType::Email, 'first@example.test');
    $login->markVerified();
    $token = app('auth.password')->broker('users')->createToken($this->user);
    auth()->logout();

    Livewire::test(ResetPassword::class, ['email' => $login->value, 'token' => $token])
        ->fillForm(['password' => 'changed-password', 'passwordConfirmation' => 'changed-password'])
        ->call('resetPassword')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    expect(Hash::check('changed-password', $this->user->fresh()->password))->toBeTrue();
});

it('signs in through the standard email login form', function () {
    $login = Login::register($this->user, LoginType::Email, 'signin@example.test');
    $login->markVerified();
    auth()->logout();

    Livewire::test(LoginPage::class)
        ->fillForm(['email' => 'SIGNIN@example.test', 'password' => 'password'])
        ->call('authenticate')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    expect(auth()->id())->toBe($this->user->id);
});

it('requests a reset through another email and delivers it to the default email', function () {
    Login::register($this->user, LoginType::Email, 'primary@example.test');
    $login = Login::register($this->user, LoginType::Email, 'recover@example.test');
    $login->markVerified();
    auth()->logout();

    Livewire::test(\Filament\Auth\Pages\PasswordReset\RequestPasswordReset::class)
        ->fillForm(['email' => 'RECOVER@example.test'])
        ->call('request')
        ->assertHasNoFormErrors()
        ->assertNotified();

    Notification::assertSentTo(
        $this->user,
        \Filament\Auth\Notifications\ResetPassword::class,
        fn ($notification) => str_contains($notification->url, 'primary%40example.test'),
    );
});
