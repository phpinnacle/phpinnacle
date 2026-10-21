<?php

use Filament\Facades\Filament;
use Filament\Panel;
use Filament\PanelRegistry;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as OAuthUser;
use Livewire\Component;
use PHPinnacle\Cerber\AuthProvider;
use PHPinnacle\Cerber\CerberPlugin;
use PHPinnacle\Cerber\Contracts\SocialAccountContext;
use PHPinnacle\Cerber\Enums\UserStatus;
use PHPinnacle\Cerber\Exceptions\OAuthException;
use PHPinnacle\Cerber\Http\Controllers\OAuth\CallbackController;
use PHPinnacle\Cerber\Http\Controllers\OAuth\TestController;
use PHPinnacle\Cerber\Models\Login;
use PHPinnacle\Cerber\Models\Provider;
use PHPinnacle\Cerber\Models\SocialAccount;
use PHPinnacle\Cerber\Models\Tenant;
use PHPinnacle\Cerber\Models\User;
use PHPinnacle\Cerber\OAuth\OAuthAttempt;
use PHPinnacle\Cerber\Resources\Providers\Schemas\ProviderForm;
use PHPinnacle\Cerber\Resources\Providers\Tables\ProviderTable;
use PHPinnacle\Cerber\Services\ProviderRegistry;
use PHPinnacle\Cerber\Services\SessionOAuth;
use PHPinnacle\Cerber\Services\SocialAccountContextRepository;
use PHPinnacle\Cerber\Services\UserLinker;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    $this->artisan('migrate')->assertSuccessful();
    $this->tenant = Tenant::default('default');

    $this->admin = User::register(new Login('admin@example.test', 'password', ['admin']), 'Admin');
    $this->admin->save();
    $this->customer = User::register(new Login('customer@example.test', 'password', ['web']), 'Customer');
    $this->customer->save();

    $this->context = fn (string $name, string $guard, User $user) => new class($name, $guard, $user) implements
        SocialAccountContext {
        public int $resolutions = 0;

        public function __construct(
            private string $name,
            private string $guard,
            private User $user,
        ) {}

        public function name(): string
        {
            return $this->name;
        }

        public function guard(): string
        {
            return $this->guard;
        }

        public function resolve(SocialiteUser $user): User
        {
            $this->resolutions++;

            return $this->user;
        }
    };
    $this->adminContext = ($this->context)('admin', 'admin', $this->admin);
    $this->webContext = ($this->context)('web', 'api', $this->customer);
    $this->provider = Provider::query()->create([
        'name' => 'Google',
        'type' => GoogleProvider::class,
        'config' => ['client_id' => 'client', 'client_secret' => 'secret'],
        'context' => 'web',
        'is_active' => true,
    ]);
    $this->adminProvider = $this->provider->replicate();
    $this->adminProvider->fill(['name' => 'Google for admin', 'context' => 'admin'])->save();
    $this->identity = new OAuthUser()->map(['id' => 'google-user', 'email' => 'same@example.test']);
    $this->identity->token = 'access-token';
    config()->set([
        'auth.providers.users.model' => User::class,
        'auth.guards.admin' => ['driver' => 'session', 'provider' => 'users'],
        'auth.guards.api' => ['driver' => 'jwt', 'provider' => 'users'],
        'cache.default' => 'array',
        'session.driver' => 'array',
        'jwt.secret' => str_repeat('s', 64),
        'phpinnacle-cerber.api.callback_url' => 'https://frontend.example/oauth/callback',
    ]);
    app(SocialAccountContextRepository::class)->add($this->adminContext);
    app(SocialAccountContextRepository::class)->add($this->webContext);
    app(ProviderRegistry::class)->add(AuthProvider::google());

    $this->mockOAuth = function (array $responses) {
        $client = new Client(['handler' => HandlerStack::create(new MockHandler($responses))]);
        $definition = Mockery::mock(AuthProvider::class);
        $definition->shouldReceive('getClass')->andReturn(GoogleProvider::class);
        $definition
            ->shouldReceive('driver')
            ->andReturnUsing(
                fn (array $config) => Socialite::buildProvider(GoogleProvider::class, $config)->setHttpClient($client),
            );
        app(ProviderRegistry::class)->add($definition);
    };
    $this->oauthResponses = [
        new Response(200, [], '{"access_token":"provider-token","expires_in":3600,"token_type":"Bearer"}'),
        new Response(200, [], '{"sub":"google-user","email":"same@example.test","name":"Social User"}'),
    ];
    $this->verifier = str_repeat('v', 55);
    $this->apiBase = '/api/auth/oauth/web/' . $this->provider->id;
    $this->startApi = function () {
        $response = $this->getJson(
            $this->apiBase . '/redirect?'
                . http_build_query([
                    'code_challenge' => OAuthAttempt::challenge($this->verifier),
                ]),
        )->assertOk();
        parse_str(parse_url($response->json('redirect_url'), PHP_URL_QUERY), $parameters);

        return $parameters;
    };
});

it('registers contexts by name in the shared repository', function () {
    $repository = app(SocialAccountContextRepository::class);
    $repository->add($this->adminContext);
    $repository->add($this->webContext);

    expect(app(SocialAccountContextRepository::class)->get('admin'))
        ->toBe($this->adminContext)
        ->and($repository->all())
        ->toBe(['admin' => $this->adminContext, 'web' => $this->webContext]);
});

it('resolves and persists the same social identity independently in two contexts', function () {
    $linker = app(UserLinker::class);
    $admin = $linker->findOrCreateUser($this->adminContext, $this->adminProvider, $this->identity);
    $customer = $linker->findOrCreateUser($this->webContext, $this->provider, $this->identity);

    expect($admin->is($this->admin))
        ->toBeTrue()
        ->and($customer->is($this->customer))
        ->toBeTrue()
        ->and(SocialAccount::query()->count())
        ->toBe(2);

    $again = $linker->findOrCreateUser($this->adminContext, $this->adminProvider, $this->identity);
    expect($again->is($this->admin))
        ->toBeTrue()
        ->and($this->adminContext->resolutions)
        ->toBe(1)
        ->and($this->webContext->resolutions)
        ->toBe(1);
});

it('unlinks only the selected context even when both contexts use the same user', function () {
    SocialAccount::link($this->adminProvider, $this->admin, $this->identity);
    SocialAccount::link($this->provider, $this->admin, $this->identity);
    SocialAccount::unlink($this->admin, $this->adminProvider);

    expect(SocialAccount::linked($this->adminContext, $this->admin))
        ->toBe([])
        ->and(SocialAccount::linked($this->webContext, $this->admin))
        ->toBe([$this->provider->id]);
});

it('filters provider availability by context and active state', function () {
    $this->provider->update(['is_active' => false]);
    expect(Provider::valid($this->adminContext)->modelKeys())
        ->toBe([$this->adminProvider->id])
        ->and(Provider::valid($this->webContext))
        ->toBeEmpty()
        ->and($this->provider->isAvailableTo($this->webContext))
        ->toBeFalse();

    $this->adminProvider->update(['is_active' => false]);
    expect($this->adminProvider->isAvailableTo($this->adminContext))->toBeFalse();
});

it('issues a frontend JWT without signing in to the panel session', function () {
    ($this->mockOAuth)($this->oauthResponses);
    $parameters = ($this->startApi)();
    $response = $this
        ->postJson($this->apiBase . '/callback', [
            'state' => $parameters['state'],
            'code' => 'code',
            'code_verifier' => $this->verifier,
        ])
        ->assertOk()
        ->assertJsonPath('user.id', $this->customer->id);

    expect($response->json('access_token'))
        ->toBeString()
        ->and(auth('admin')->guest())
        ->toBeTrue()
        ->and(auth('web')->guest())
        ->toBeTrue()
        ->and(SocialAccount::find($this->provider, 'google-user')->user_id)
        ->toBe($this->customer->id)
        ->and(SocialAccount::find($this->adminProvider, 'google-user'))
        ->toBeNull();

    $this->postJson($this->apiBase . '/callback', [
        'state' => $parameters['state'],
        'code' => 'code',
        'code_verifier' => $this->verifier,
    ])->assertUnauthorized();
});

it('rejects callbacks from another browser or another context before contacting the provider', function () {
    ($this->mockOAuth)($this->oauthResponses);
    $parameters = ($this->startApi)();
    $this->postJson($this->apiBase . '/callback', [
        'state' => $parameters['state'],
        'code' => 'code',
        'code_verifier' => str_repeat('x', 55),
    ])->assertUnauthorized();

    config()->set('auth.guards.other', ['driver' => 'jwt', 'provider' => 'users']);
    app(SocialAccountContextRepository::class)->add(($this->context)('other', 'other', $this->admin));
    $otherProvider = $this->provider->replicate();
    $otherProvider->fill(['context' => 'other'])->save();
    $this->postJson('/api/auth/oauth/other/' . $otherProvider->id . '/callback', [
        'state' => $parameters['state'],
        'code' => 'code',
        'code_verifier' => $this->verifier,
    ])->assertUnauthorized();

    $this->postJson($this->apiBase . '/callback', [
        'state' => $parameters['state'],
        'code' => 'code',
        'code_verifier' => $this->verifier,
    ])->assertOk();
});

it('rejects unknown contexts and session contexts on JWT endpoints', function () {
    $this->getJson('/api/auth/oauth/unknown/providers')->assertNotFound();
    $this->getJson('/api/auth/oauth/admin/providers')->assertNotFound();
    $this->getJson('/api/auth/oauth/web/providers')->assertOk()->assertJsonCount(1);
    $this->getJson(
        '/api/auth/oauth/web/' . $this->adminProvider->id . '/redirect?code_challenge='
            . OAuthAttempt::challenge($this->verifier),
    )->assertNotFound();
});

it('enforces provider context and active state on direct redirect and callback requests', function () {
    $parameters = ($this->startApi)();
    $this->provider->update(['context' => 'admin']);
    $this->getJson('/api/auth/oauth/web/providers')->assertOk()->assertExactJson([]);
    $this->getJson(
        $this->apiBase . '/redirect?code_challenge=' . OAuthAttempt::challenge($this->verifier),
    )->assertNotFound();
    $this->postJson($this->apiBase . '/callback', [
        'state' => $parameters['state'],
        'code' => 'code',
        'code_verifier' => $this->verifier,
    ])->assertNotFound();

    $this->provider->update(['context' => 'web', 'is_active' => false]);
    $this->getJson(
        $this->apiBase . '/redirect?code_challenge=' . OAuthAttempt::challenge($this->verifier),
    )->assertNotFound();
    $this->postJson($this->apiBase . '/callback', [
        'state' => $parameters['state'],
        'code' => 'code',
        'code_verifier' => $this->verifier,
    ])->assertNotFound();
});

it('validates external OAuth parameters', function () {
    $this
        ->getJson($this->apiBase . '/redirect')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('code_challenge');
    $this
        ->getJson($this->apiBase . '/redirect?code_challenge[]=value')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('code_challenge');
    $this
        ->postJson($this->apiBase . '/callback', [
            'state' => [],
            'code' => [],
            'code_verifier' => 'short',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['state', 'code', 'code_verifier']);
});

it('expires abandoned OAuth attempts', function () {
    $parameters = ($this->startApi)();
    $this->travel(11)->minutes();
    $this->postJson($this->apiBase . '/callback', [
        'state' => $parameters['state'],
        'code' => 'code',
        'code_verifier' => $this->verifier,
    ])->assertUnauthorized();
});

it('returns a safe error when the upstream provider fails', function () {
    ($this->mockOAuth)([new Response(500, [], 'private provider diagnostics')]);
    $parameters = ($this->startApi)();
    $this
        ->postJson($this->apiBase . '/callback', [
            'state' => $parameters['state'],
            'code' => 'code',
            'code_verifier' => $this->verifier,
        ])
        ->assertUnauthorized()
        ->assertExactJson(['message' => __('phpinnacle-cerber::auth.errors.callback_failed')]);
});

it('passes configured OAuth scopes to Socialite', function () {
    $config = $this->provider->config;
    $config['scopes'] = ['https://www.googleapis.com/auth/calendar.readonly'];
    $this->provider->update(['config' => $config]);
    $parameters = ($this->startApi)();
    expect($parameters['scope'])
        ->toContain('https://www.googleapis.com/auth/calendar.readonly')
        ->and($parameters['code_challenge_method'])
        ->toBe('S256');
});

it('does not issue JWTs for blocked users with an existing social account', function () {
    SocialAccount::link($this->provider, $this->customer, $this->identity);
    $this->customer->update(['status' => UserStatus::Blocked]);
    ($this->mockOAuth)($this->oauthResponses);
    $parameters = ($this->startApi)();
    $this
        ->postJson($this->apiBase . '/callback', [
            'state' => $parameters['state'],
            'code' => 'code',
            'code_verifier' => $this->verifier,
        ])
        ->assertUnauthorized()
        ->assertExactJson(['message' => __('phpinnacle-cerber::auth.errors.access_denied')]);
});

it('unlinks through the API only with a token belonging to that context', function () {
    SocialAccount::link($this->adminProvider, $this->customer, $this->identity);
    SocialAccount::link($this->provider, $this->customer, $this->identity);
    $guard = auth('api');
    $wrongToken = $guard->claims(['social_account_context' => 'another'])->login($this->customer);
    $guard->forgetUser();
    $this
        ->withToken($wrongToken)
        ->deleteJson($this->apiBase . '/link')
        ->assertForbidden();
    expect(SocialAccount::query()->count())->toBe(2);

    $token = $guard->claims(['social_account_context' => 'web'])->login($this->customer);
    $guard->forgetUser();
    $this
        ->withToken($token)
        ->deleteJson('/api/auth/oauth/web/' . $this->adminProvider->id . '/link')
        ->assertNotFound();
    expect(SocialAccount::query()->count())->toBe(2);
    $guard->forgetUser();
    $this
        ->withToken($token)
        ->deleteJson($this->apiBase . '/link')
        ->assertOk();
    expect(SocialAccount::linked($this->webContext, $this->customer))
        ->toBe([])
        ->and(SocialAccount::linked($this->adminContext, $this->customer))
        ->toBe([$this->adminProvider->id]);
});

it('allows users without roles to sign in through independent panel OAuth contexts', function () {
    config()->set('auth.guards.second', ['driver' => 'session', 'provider' => 'users']);
    $second = ($this->context)('second', 'second', $this->customer);
    app(SocialAccountContextRepository::class)->add($second);
    $this->provider->update(['context' => 'second']);
    $providers = ['admin' => $this->adminProvider, 'second' => $this->provider];
    expect($this->admin->roles()->exists())->toBeFalse()->and($this->customer->roles()->exists())->toBeFalse();
    $panels = [];

    foreach ([$this->adminContext, $second] as $context) {
        $panel = Panel::make()
            ->id($context->name())
            ->path($context->name())
            ->homeUrl('/' . $context->name());
        $panel->plugin(CerberPlugin::make()->socialAccountContext($context->name())->withoutResources());
        app(PanelRegistry::class)->register($panel);
        Route::get('/' . $context->name() . '/callback', fn () => 'callback')->name(
            'filament.' . $context->name() . '.auth.callback',
        );
        $panels[$context->name()] = $panel;
    }
    session()->start();
    app('request')->setLaravelSession(session()->driver());
    ($this->mockOAuth)([...$this->oauthResponses, ...$this->oauthResponses]);
    $oauth = app(SessionOAuth::class);
    $states = [];

    foreach ([$this->adminContext, $second] as $context) {
        Filament::setCurrentPanel($panels[$context->name()]);
        if ($context->name() === 'admin') {
            expect(fn () => $oauth->redirect($context, $providers['second']))
                ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        }
        $redirect = $oauth->redirect($context, $providers[$context->name()]);
        parse_str(parse_url($redirect->getTargetUrl(), PHP_URL_QUERY), $parameters);
        $states[$context->name()] = $parameters['state'];
    }

    foreach ([$this->adminContext, $second] as $context) {
        Filament::setCurrentPanel($panels[$context->name()]);
        $request = app('request');
        $request->merge(['state' => $states[$context->name()], 'code' => 'code']);
        $attempt = $oauth->attempt($request);
        $identity = $oauth->user($context, $providers[$context->name()], $request, $attempt);
        $oauth->login($context, $providers[$context->name()], $identity);

        if ($context->name() === 'admin') {
            expect(auth('second')->guest())->toBeTrue();
        }
    }

    expect(auth('admin')->id())
        ->toBe($this->admin->id)
        ->and(auth('second')->id())
        ->toBe($this->customer->id)
        ->and(auth('web')->guest())
        ->toBeTrue();
});

it('denies panel OAuth login for inactive users without roles', function (UserStatus $status) {
    Filament::setCurrentPanel(Panel::make()->id('admin')->homeUrl('/admin'));
    $this->admin->update(['status' => $status]);

    expect(fn () => app(SessionOAuth::class)->login($this->adminContext, $this->adminProvider, $this->identity))
        ->toThrow(OAuthException::class, 'The user cannot access this panel.');

    expect(auth('admin')->guest())->toBeTrue();
})->with([UserStatus::Blocked, UserStatus::Archived]);

it('authenticates application routes only with the selected JWT context', function () {
    Route::get('/context-user', fn () => ['id' => auth()->id()])->middleware('cerber.context:web');
    $this->getJson('/context-user')->assertUnauthorized();
    $guard = auth('api');
    $wrong = $guard->claims(['social_account_context' => 'other'])->login($this->customer);
    $guard->forgetUser();
    $this->withToken($wrong)->getJson('/context-user')->assertForbidden();

    $correct = $guard->claims(['social_account_context' => 'web'])->login($this->customer);
    $guard->forgetUser();
    $this->withToken($correct)->getJson('/context-user')->assertOk()->assertJsonPath('id', $this->customer->id);
});

it('links a profile only to the user who started that context attempt', function (bool $switchUser) {
    $panel = Panel::make()->id('admin')->path('admin')->homeUrl('/admin')->default();
    $panel->plugin(CerberPlugin::make()->socialAccountContext('admin')->withoutResources());
    app(PanelRegistry::class)->register($panel);
    Filament::setCurrentPanel($panel);
    Route::get('/admin/oauth/callback', fn () => 'callback')->name('filament.admin.auth.callback');
    Route::get('/admin/profile', fn () => 'profile')->name('filament.admin.profile');
    session()->start();
    app('request')->setLaravelSession(session()->driver());
    auth('admin')->login($this->admin);
    ($this->mockOAuth)($this->oauthResponses);
    $redirect = app(SessionOAuth::class)->redirect(
        $this->adminContext,
        $this->adminProvider,
        linkUserId: $this->admin->id,
    );
    parse_str(parse_url($redirect->getTargetUrl(), PHP_URL_QUERY), $parameters);
    $request = app('request');
    $request->merge(['state' => $parameters['state'], 'code' => 'code']);
    if ($switchUser) {
        auth('admin')->login($this->customer);
        expect(fn () => app(CallbackController::class)(
            $this->adminProvider,
            $request,
        ))
            ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        expect(SocialAccount::query()->count())->toBe(0);

        return;
    }

    app(CallbackController::class)($this->adminProvider, $request);

    expect(SocialAccount::find($this->adminProvider, 'google-user')->user_id)
        ->toBe($this->admin->id)
        ->and(SocialAccount::find($this->provider, 'google-user'))
        ->toBeNull();
})->with([false, true]);

it('does not reassign an external account already linked to the same provider', function () {
    SocialAccount::link($this->adminProvider, $this->admin, $this->identity);

    expect(fn () => SocialAccount::link($this->adminProvider, $this->customer, $this->identity))
        ->toThrow(\Illuminate\Database\UniqueConstraintViolationException::class);
    expect(SocialAccount::find($this->adminProvider, 'google-user')->user_id)->toBe($this->admin->id);
});

it('renders an OAuth test result without signing in or linking an account', function (bool $fails) {
    $panel = Panel::make()->id('admin')->path('admin')->default();
    $panel->plugin(CerberPlugin::make()->socialAccountContext('admin')->withoutResources());
    app(PanelRegistry::class)->register($panel);
    Filament::setCurrentPanel($panel);
    Route::get('/admin/oauth/callback', fn () => 'callback')->name('filament.admin.auth.callback');
    session()->start();
    $request = app('request');
    $request->setLaravelSession(session()->driver());
    ($this->mockOAuth)($fails ? [new Response(500, [], 'private provider diagnostics')] : $this->oauthResponses);

    $redirect = app(TestController::class)($this->adminProvider);
    parse_str(parse_url($redirect->getTargetUrl(), PHP_URL_QUERY), $parameters);
    $request->merge(['state' => $parameters['state'], 'code' => 'code']);
    $result = app(CallbackController::class)($this->adminProvider, $request);

    expect($result->name())
        ->toBe('phpinnacle-cerber::oauth.test-result')
        ->and($result->getData()['success'])
        ->toBe(!$fails)
        ->and($result->getData()['userData']['id'] ?? null)
        ->toBe($fails ? null : 'google-user')
        ->and($result->render())
        ->toContain(__('phpinnacle-cerber::auth.test.' . ($fails ? 'failure' : 'success')))
        ->not
        ->toContain('private provider diagnostics', 'provider-token')
        ->and(auth('admin')->guest())
        ->toBeTrue()
        ->and(SocialAccount::query()->count())
        ->toBe(0);
})->with([false, true]);

function cerber_provider_form(?Provider $record = null): Schema
{
    $component = new class extends Component implements HasSchemas {
        use InteractsWithSchemas;

        /** @var array<string, mixed> */
        public array $data = [];
    };
    $component->setId('cerber-provider-form');
    $component->setName('cerber-provider-form');

    return ProviderForm::configure(
        Schema::make($component)
            ->model($record ?? Provider::class)
            ->operation($record === null ? 'create' : 'edit')
            ->statePath('data'),
    );
}

it('creates separate providers of the same type through the panel form', function () {
    $form = cerber_provider_form();
    $form->fill([
        'name' => 'Another Google for web',
        'type' => GoogleProvider::class,
        'context' => 'web',
        'is_active' => true,
        'config' => ['client_id' => 'web-client', 'client_secret' => 'web-secret'],
    ]);
    $provider = Provider::query()->create($form->getState());

    expect($provider->context)
        ->toBe('web')
        ->and($provider->type)
        ->toBe($this->adminProvider->type)
        ->and($provider->config['client_id'])
        ->toBe('web-client');
});

it('rejects an unknown or multiple sign-in destinations in the panel form', function (mixed $context) {
    $form = cerber_provider_form();
    $form->fill([
        'name' => 'Google',
        'type' => GoogleProvider::class,
        'context' => $context,
        'is_active' => true,
        'config' => ['client_id' => 'client', 'client_secret' => 'secret'],
    ]);

    try {
        $form->getState();
        $this->fail('The provider form accepted an invalid context.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('data.context');
    }
})->with(['unknown' => ['unknown'], 'multiple' => [['admin', 'web']]]);

it('keeps existing provider bindings in their original context when editing the form', function () {
    SocialAccount::link($this->adminProvider, $this->admin, $this->identity);
    $form = cerber_provider_form($this->adminProvider);
    $form->fill([
        'name' => 'Renamed Google',
        'type' => GoogleProvider::class,
        'context' => 'web',
        'is_active' => true,
        'config' => $this->adminProvider->config,
    ]);
    $this->adminProvider->update($form->getState());

    expect($this->adminProvider->refresh()->name)
        ->toBe('Renamed Google')
        ->and($this->adminProvider->context)
        ->toBe('admin')
        ->and(SocialAccount::linked($this->webContext, $this->admin))
        ->toBe([])
        ->and(SocialAccount::linked($this->adminContext, $this->admin))
        ->toBe([$this->adminProvider->id]);
});

it('shows the matching callback URL for panel and frontend providers', function () {
    $panel = Panel::make()->id('admin')->path('admin')->default();
    $panel->plugin(CerberPlugin::make()->socialAccountContext('admin')->withoutResources());
    app(PanelRegistry::class)->register($panel);
    Filament::setCurrentPanel($panel);
    Route::get('/admin/oauth/{provider}/callback', fn () => 'callback')->name('filament.admin.auth.callback');
    $table = ProviderTable::configure(
        Table::make(Mockery::mock(HasTable::class)),
    );
    $column = $table->getColumn('name');

    expect($column->record($this->adminProvider)->getDescriptionBelow())
        ->toBe($panel->route('auth.callback', ['provider' => $this->adminProvider->id]))
        ->and($column->record($this->provider)->getDescriptionBelow())
        ->toBe('https://frontend.example/oauth/callback?context=web&provider=' . $this->provider->id);
});

it('can edit a frontend provider in a panel without social login routes', function () {
    $panel = Panel::make()->id('management')->path('management')->default();
    $panel->plugin(CerberPlugin::make()->withoutResources());
    app(PanelRegistry::class)->register($panel);
    Filament::setCurrentPanel($panel);
    $page = new class extends \PHPinnacle\Cerber\Resources\Providers\Pages\EditProvider {
        public function headerActions(): array
        {
            return $this->getHeaderActions();
        }
    };
    $page->record = $this->provider;

    expect($page->headerActions()[0]->isVisible())->toBeFalse();
});
