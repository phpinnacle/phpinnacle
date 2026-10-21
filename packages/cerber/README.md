# Cerber for Filament

[![Latest Version on Packagist](https://img.shields.io/packagist/v/phpinnacle/cerber.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/cerber)
[![Total Downloads](https://img.shields.io/packagist/dt/phpinnacle/cerber.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/cerber)

Cerber is the authentication and authorization layer for PHPinnacle Filament applications. It provides users, roles and permissions, OAuth providers, API tokens, impersonation, profile management and Filament resources as one configurable panel plugin.

## Features

- User, role and OAuth provider Filament resources.
- Database-backed roles and permissions with grouped Filament resource, page and widget permissions.
- Google, Yandex, GitHub and Facebook Socialite provider definitions.
- Independent OAuth contexts with application-owned user resolution and account linking.
- Session login for panels and JWT login for frontend applications.
- Laravel Sanctum token model registration; application-side user integration is required.
- User impersonation via `laravel-impersonate`.
- Configurable profile form, developer identities, authentication contexts and custom permission groups.
- Optional tenancy and custom database connection.

## Requirements and installation

- PHP `^8.4`
- Filament `^4.0|^5.0`, Sanctum `^4.0`, Socialite `^5.0`, and `php-open-source-saver/jwt-auth` `^2.9`
- `phpinnacle/common` and `phpinnacle/tempo` `^1.0`

Composer resolves the compatible Laravel version from these dependencies. The current monorepo development setup uses Laravel 13 and Filament 5.

```bash
composer require phpinnacle/cerber
```

The migrations create Cerber-owned `users`, `password_reset_tokens`, and `sessions` tables, among others. Reconcile existing application migrations for those tables before running them. Configure the Laravel authentication provider used by your panel to use `PHPinnacle\Cerber\Models\User`.

Publish configuration before running migrations when customizing the database connection or provider tenancy; it also controls navigation, permissions, and OAuth provider registration:

```bash
php artisan vendor:publish --tag="phpinnacle-cerber-config"
```

Set `phpinnacle-cerber.connection` to a configured database connection name, or leave it `null` to use the application's default connection. Both package models and migrations use this setting.

If enabling tenancy, also supply the provider tenancy configuration described below before running migrations. Then publish and run the package migrations:

```bash
php artisan vendor:publish --tag="phpinnacle-cerber-migrations"
php artisan migrate
```

On a fresh database, create the default tenant once with `PHPinnacle\Cerber\Models\Tenant::default('default')` before creating users or roles that use the default tenant ID. Migrations do not seed this record. `User::register()` returns an unsaved model; call `save()` to persist it.

## Registering the plugin

Register the `admin` OAuth context as described below before configuring this panel.

```php
use PHPinnacle\Cerber\CerberPlugin;

$panel->plugin(
    CerberPlugin::make()
        ->socialAccountContext('admin')
        ->developers(['developer@example.com']),
);
```

Use `withoutProviders()`, `withoutRoles()`, `withoutUsers()` or `withoutResources()` when the application supplies that UI itself. `modifyProfileForm()` receives the Filament schema and profile page for application-specific fields.

`developers()` accepts a list of email addresses or an email-to-label map; `getDevelopers()` returns the configured map. Developer login is available only outside production and requires an existing user with access to the panel and requested tenant. The `DeveloperLogin` service handles authentication and session regeneration; `CerberPlugin::auth()` delegates to it.

## Tenancy

`tenancy()` configures the Cerber `Tenant` model and a `{tenant:domain}` subdomain under the host from `app.url`. `scopes(Order::class)` adds an application model to the tenancy registrations; it does not enable tenancy by itself. Scoped models must have a `tenant_id` column. Roles, invitations, and OAuth providers are registered by default.

With tenancy enabled, scoped models receive the current tenant when created, unless `tenant_id` is already set; without a current tenant they use `Tenant::DEFAULT`. Repeated panel boots reuse the registered creation listener. A `tenant` relationship is also registered for these models.

The OAuth provider migration adds `tenant_id` only when these entries are supplied in `config/phpinnacle-cerber.php` before running the migration:

```php
'tenancy' => [
    'model' => PHPinnacle\Cerber\Models\Tenant::class,
    'default' => PHPinnacle\Cerber\Models\Tenant::DEFAULT,
],
```

These entries are absent from the default configuration. Enabling tenancy after the provider table has been created requires an application migration to add that column.

`scopes()` supplies creation defaults and relationships, not query isolation. The plugin disables Filament's default resource tenant scoping with `Resource::scopeToTenant(false)`; applications must enforce the tenant filtering and authorization they require. `User::canAccessTenant()` checks membership through assigned roles.

## Roles and permissions

```php
use PHPinnacle\Cerber\Models\Permission;

$permission = Permission::register('orders.view');
$role->grant($permission);

if ($role->able('orders.view')) {
    // The role grants this permission.
}
```

Resource classes may implement `HasCustomPermissions` to supply permission prefixes, which Cerber combines with the snake-case model name, such as `view_user`. `CerberPlugin::guards($group, $permissions)` registers custom permission names and labels grouped by section. The `exclude` configuration removes selected Filament pages, widgets or resources from generated permission groups; `permissions` overrides resource permission prefixes, and `translations` overrides their translation prefixes.

`Role::register($tenant, $name, $permissions)` finds or creates the named role within that tenant. `Role::grant(...$permissions)` replaces the role's entire permission set; calling it without arguments removes all grants. It accepts permission models, UUIDs, or names, registers missing names, and commits changes on the configured Cerber database connection. Subsequent `Role::able()` calls on the same instance see the updated grants.

`User::able($ability, $tenant)` reads permissions through the user's roles in that tenant, defaulting to `Tenant::DEFAULT`. It does not include directly attached user permissions. The current permission checks do not filter the `is_active` flags on roles or permissions, so changing those flags alone does not revoke assigned grants.

`php artisan auth:sync-permissions` replaces every system role's grants with the resource permission catalog for the current Filament panel, plus registered custom guards. It leaves ordinary roles unchanged. When there is no current panel, no resource permissions are included; page and widget permissions are not included by this command.

The user status action delegates to `User::toggleArchived()`: active users become archived, while archived or blocked users become active.

## OAuth contexts

Implement `PHPinnacle\Cerber\Contracts\SocialAccountContext` for each independent side of the application. The context supplies its stable name, Laravel guard, and user resolver:

```php
use Laravel\Socialite\Contracts\User as SocialiteUser;
use PHPinnacle\Cerber\Contracts\SocialAccountContext;
use PHPinnacle\Cerber\Models\User;

final class AdminSocialAccountContext implements SocialAccountContext
{
    public function name(): string
    {
        return 'admin';
    }

    public function guard(): string
    {
        return 'admin';
    }

    public function resolve(SocialiteUser $user): User
    {
        return User::query()
            ->where('email', $user->getEmail())
            ->whereJsonContains('panels', 'admin')
            ->firstOrFail();
    }
}
```

Implement `WebSocialAccountContext` with `name()` returning `web`, `guard()` returning `api`, and its own `resolve()` policy. For example, it can find users whose `panels` contains `web`, or create a user according to the application's registration rules. Both contexts return persisted `Cerber\Models\User` instances. Contexts do not change the user table's global email uniqueness constraint.

`resolve()` runs only when the provider identity has no existing binding for that provider. It owns user lookup, registration, email/domain eligibility, and initial role assignment. Cerber saves the binding in the same database transaction; later logins use that binding directly. There is no global email lookup across contexts. Resolution and binding persistence happen before the final sign-in access check.

Panel OAuth login requires `User::canAccessPanel()` to allow access; the base implementation checks only user status, not the `panels` attribute or role membership. API OAuth login also requires an active user. Neither OAuth login flow requires an assigned role. The `panels` filter in the resolver example is application policy and runs only for a new binding. Resource and action permissions are checked separately from sign-in.

Register stateless context objects in the application's service provider `register()` method, before configuring panels:

```php
use PHPinnacle\Cerber\Services\SocialAccountContextRepository;

$contexts = $this->app->make(SocialAccountContextRepository::class);
$contexts->add($this->app->make(AdminSocialAccountContext::class));
$contexts->add($this->app->make(WebSocialAccountContext::class));
```

The repository exposes `get('admin')` and `all()`, keyed by name. Context implementations must not retain request, tenant, or user objects between calls. Context names are stored on provider records; renaming a context requires updating those records.

Use a distinct guard for each independent context. For example, configure `auth.guards.admin` with the `session` driver and `auth.guards.api` with the `jwt` driver, both using a provider configured for `PHPinnacle\Cerber\Models\User`. Generate the JWT signing secret with `php artisan jwt:secret`. The panel uses the guard supplied by its selected context:

```php
$panel->plugin(CerberPlugin::make()->socialAccountContext('admin'));
```

A single-context installation registers just one context. A panel without `socialAccountContext()` has no social login or profile-linking actions.

## OAuth providers and account bindings

Register Socialite provider classes in `phpinnacle-cerber.auth_providers`, for example:

```php
'auth_providers' => [
    Laravel\Socialite\Two\GoogleProvider::class,
    Laravel\Socialite\Two\GithubProvider::class,
],
```

Create a separate provider record for each sign-in destination. For example, create “Google for admin” with context `admin` and “Google for web” with context `web`. Both records may use Google, with their own client credentials and OAuth scopes. Select exactly one registered context when creating a provider; its type and context cannot be changed through the edit form, so existing account bindings cannot move to another destination.

The provider list shows the callback URL to register with the OAuth service. Filament handles its providers through session login; frontend applications use the JWT API. There is no session/JWT switch in the provider form. Both entry points reject a provider assigned to another context. The panel's connection-test action is shown for providers whose context matches the panel; it does not sign in or create an account binding.

Bindings are unique by provider and external account ID, with one account per user and provider. Their context comes from the provider and is not duplicated on the binding. The same external identity can belong to different local users through the separate admin and web providers. Linking or unlinking affects only the selected provider. The panel profile page lists providers for its context. For application-owned account management:

```php
use PHPinnacle\Cerber\Models\SocialAccount;

SocialAccount::linked($context, $user);
SocialAccount::link($provider, $user, $verifiedSocialiteUser);
SocialAccount::unlink($user, $provider);
```

`linked()` returns provider IDs. `unlink()` deletes the local binding; it does not revoke tokens at the external provider. Only pass identities obtained through a verified OAuth flow to `link()`, and authorize the target user and provider context in the calling application. An external identity already bound to another user through the same provider cannot be reassigned by linking.

## Frontend JWT flow

Set `CERBER_API_CALLBACK_URL` to the frontend callback page. Register the exact callback URLs, including their `context` and `provider` query parameters, in the OAuth provider console. Panel callback URLs remain `/auth/{provider}/callback` under the panel path. OAuth2 providers must support the authorization-code flow; Cerber sends S256 PKCE parameters.

The API routes are:

```text
GET    /api/auth/oauth/{socialAccountContext}/providers
GET    /api/auth/oauth/{socialAccountContext}/{provider}/redirect?code_challenge=...
POST   /api/auth/oauth/{socialAccountContext}/{provider}/callback
DELETE /api/auth/oauth/{socialAccountContext}/{provider}/link
```

`socialAccountContext` is the registered context name, such as `web`; `provider` is the provider record's ID. The route names are `cerber.oauth.providers`, `cerber.oauth.redirect`, `cerber.oauth.callback`, and `cerber.oauth.unlink`. Only contexts configured with a JWT guard are available through these endpoints.

1. Generate a cryptographically random PKCE verifier in the browser (43–128 characters from `A-Z`, `a-z`, `0-9`, `-`, `.`, `_`, `~`). Compute its SHA-256 hash and encode it as unpadded base64url to obtain the 43-character `code_challenge`.
2. Send the challenge to the redirect endpoint. Store the verifier in the originating tab's `sessionStorage`, indexed by the `state` from the returned `redirect_url`, then navigate to that URL. Never put the verifier in the authorization URL.
3. On the frontend callback page, retrieve that locally stored verifier. If it is missing, reject the callback. POST `state`, `code`, and `code_verifier` to the matching context/provider callback endpoint, then remove the saved verifier.
4. The response contains `access_token`, `token_type`, `expires_in` in seconds, and `user` with `id`, `email`, and `status`. It does not sign in to a panel session.

Attempts expire after ten minutes and can be exchanged once. The callback verifies the context, provider, callback URL, and browser verifier before contacting the provider.

The cache store must persist attempts between requests and support atomic locks. Workers serving the OAuth flow must share that store.

JWTs include the `social_account_context` claim. Protect application routes with the context middleware so tokens cannot be reused in a different context that shares the signing key and user model:

```php
Route::middleware('cerber.context:web')->group(function () {
    Route::get('/me', fn () => auth()->user());
});
```

`DELETE .../link` requires a bearer token issued for the selected context and removes only the authenticated user's binding in that context. Provider secrets, access tokens, and browser verifiers are credentials; do not log or expose them.

## Sanctum integration

The service provider registers `Models\AccessToken` as Sanctum's token model, and the migrations create its `access_tokens` table. The bundled `User` model does not include Sanctum's `HasApiTokens` trait or token-management methods. Applications using personal access tokens must supply that user integration. Cerber does not provide token issuance, revocation endpoints, or a token-management page; its OAuth API issues JWTs.

## Upgrading from scope-based OAuth

Existing installations need an application migration: `oauth_providers.scopes` has been replaced by the required `context` column, and `social_accounts` now requires a unique `(user_id, provider_id)` pair. Updating the package does not rerun an already published and executed migration. Assign a registered context to each existing provider and resolve duplicate bindings before adding the unique constraint.

Providers previously shared between sign-in destinations must be split into separate provider records, with bindings assigned explicitly to their intended destination. Replace the removed strategy, allowed-domain, and default-role configuration with context registrations and the context's `resolve()` policy. Update frontend requests to include the context in the API path and use the PKCE callback flow described above.

## Testing

From the standalone package checkout, with its development dependencies installed:

```bash
composer test
```

From the monorepo root:

```bash
vendor/bin/pest packages/cerber/tests --no-coverage
```

## Changelog and license

See [CHANGELOG](CHANGELOG.md). Released under the [MIT License](LICENSE.md).
