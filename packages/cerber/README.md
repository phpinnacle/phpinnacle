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
- PostgreSQL or SQLite; local login uniqueness uses a partial index.
- Filament `^4.0|^5.0`, Sanctum `^4.0`, Socialite `^5.0`, and `php-open-source-saver/jwt-auth` `^2.9`
- `phpinnacle/common` and `phpinnacle/tempo` `^1.0`

Composer resolves the compatible Laravel version from these dependencies. The current monorepo development setup uses Laravel 13 and Filament 5.

```bash
composer require phpinnacle/cerber
```

The migrations create Cerber-owned `users`, `logins`, `password_reset_tokens`, and `sessions` tables, among others. Reconcile existing application migrations for those tables before running them. Configure the Laravel authentication provider used by your panel to use `PHPinnacle\Cerber\Models\User`. Set `phpinnacle-cerber.user_model` when the owning user is an application subclass.

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

## Users and logins

`User` is the authenticated identity and owns one password, remember token, profile, permissions, tenants and MFA settings. `Login` stores identifiers in the `logins` relationship. `LoginType` defines `email`, `phone`, `login` and `oauth`; several identifiers of the same type may belong to one user. Local identifiers have a null `provider_id`, while OAuth identifiers store the configured provider UUID and the external subject as their value. Local `(type, value)` pairs are globally unique; OAuth `(type, provider_id, value)` pairs are unique within each provider.

```php
use PHPinnacle\Cerber\Enums\LoginType;
use PHPinnacle\Cerber\Models\Login;
use PHPinnacle\Cerber\Models\User;

$user = User::register('Jane Doe', ['admin']);
$user->password = 'a-strong-user-password';
$user->save();

$email = Login::register($user, LoginType::Email, 'jane@example.com');
Login::register($user, LoginType::Phone, '+48123456789');
Login::register($user, LoginType::Login, 'jane');
```

The first login of each type becomes `is_default`. Editing another local login can make it the default. Deleting the default promotes the oldest remaining login of that type. The default email receives user mail and is the password recovery address. A user without an email login cannot recover a password by email.

Configure Laravel authentication with the application User subclass when applicable:

```php
'guards' => [
    'web' => ['driver' => 'session', 'provider' => 'users'],
    'api' => ['driver' => 'jwt', 'provider' => 'users'],
],
'providers' => [
    'users' => ['driver' => 'cerber', 'model' => User::class],
],
'passwords' => [
    'users' => [
        'provider' => 'users',
        'table' => 'password_reset_tokens',
        'expire' => 60,
        'throttle' => 60,
    ],
],
```

Cerber uses Laravel's native session driver and a stateless Eloquent user provider. The provider resolves standard Filament email credentials through a local email login and checks the shared user password. Email logins must be verified before password sign-in; changing their address clears verification. The standard Filament login and password-reset pages remain in use. Phone and username identifiers can be stored and managed, while the standard page accepts email and OAuth.

`auth()->user()` returns `User`; `auth()->id()`, browser sessions and JWT subjects identify that user. Sessions do not retain a selected login. Deleting or unverifying a login prevents new sign-ins through it and preserves existing user sessions. Enable Filament's session authentication middleware to revoke password sessions after changes to the shared password. Native remember me requires a user password; users who have only OAuth can sign in without one and set a password through email recovery when an email is available.

Password recovery accepts any registered email identifier, sends the link to the user's default email and changes the shared password. Unverified email identifiers can receive recovery mail, but still require ordinary email verification before password sign-in. Changing the default email invalidates reset tokens issued for the previous address. Impersonation operates on users directly, including users without login identifiers, and does not restore access after the impersonator's password or remember token changes.

User cards and profiles show logins grouped by type; the profile uses aside sections. Add and Edit dialogs manage local values without passwords, and types cannot be changed. Edit and Delete remain inline icons. Administrative email rows also have an ellipsis menu for verification and recovery actions. Profile rows do not expose these administrative actions. Login actions save immediately and preserve drafts in the main form. The shared password is edited in the user form or a separate aside section in the profile. Profile password changes require the current password when one exists; authenticated passwordless users can set their first password without it. User cards also expose `is_password_temporary`, a stored administrative marker that does not require a password change and is not shown in the profile. `EmailAction` and `PasswordAction` build the administrative actions; `UserService` sends verification for a `Login` and password recovery for a `User`. Create a user before adding its logins.

OAuth remains a login type. Linking an identity creates an unverified email login when its email is available and not already registered. An existing email login is preserved, and an address owned by another user never links or merges those users. The nullable JSON `metadata` map stores OAuth display fields (`email`, `name`, `nickname`, `avatar`) and other application data; updating display fields preserves other keys. External provider access and refresh tokens are not persisted. Cerber issues its own JWT for API authentication.

`User::findByLogin(LoginType::Email, $email)` finds the owner and supports application subclasses.

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

`developers()` accepts a list of email addresses or an email-to-label map; `getDevelopers()` returns the configured map. Developer login is available only outside production and requires an existing verified email login whose owner can access the panel and requested tenant. The `DeveloperLogin` service authenticates that user without checking the password and regenerates the session; `CerberPlugin::auth()` delegates to it.

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
use PHPinnacle\Cerber\Enums\LoginType;
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
            ->whereHas('logins', fn ($query) => $query
                ->where('type', LoginType::Email)
                ->whereNull('provider_id')
                ->where('value', LoginType::Email->canonical($user->getEmail()))
                ->whereNotNull('verified_at'))
            ->whereJsonContains('panels', 'admin')
            ->firstOrFail();
    }
}
```

Implement `WebSocialAccountContext` with `name()` returning `web`, `guard()` returning `api`, and its own `resolve()` policy. For example, it can find users whose `panels` contains `web`, or create a user according to the application's registration rules. Both contexts return persisted `Cerber\Models\User` instances. Contexts share the global login uniqueness constraint.

`resolve()` runs only when the provider identity has no existing binding for that provider. It owns user lookup, registration, email/domain eligibility, and initial role assignment. `UserLinker::findOrCreateUser()` saves the binding in the same database transaction and returns its owning `User`; later logins use that binding directly. There is no global email lookup across contexts. Resolution and binding persistence happen before the final sign-in access check.

Panel OAuth login authenticates the user who owns the matching `LoginType::OAuth` record. `Login::canAccessPanel()` checks login availability and delegates panel access to its owner; the base owner's check requires active status. Neither OAuth flow requires a password, an email address, a role, or a particular `panels` value. The resolver owns registration and eligibility rules for new identities; resource permissions remain separate from sign-in. Matching provider email addresses do not automatically merge credentials.

Register stateless context objects in the application's service provider `register()` method, before configuring panels:

```php
use PHPinnacle\Cerber\Services\SocialAccountContextRepository;

$contexts = $this->app->make(SocialAccountContextRepository::class);
$contexts->add($this->app->make(AdminSocialAccountContext::class));
$contexts->add($this->app->make(WebSocialAccountContext::class));
```

The repository exposes `get('admin')` and `all()`, keyed by name. Context implementations must not retain request, tenant, or user objects between calls. Context names are stored on provider records; renaming a context requires updating those records.

Use a distinct guard for each independent context. For example, configure `auth.guards.admin` with the `session` driver and `auth.guards.api` with the `jwt` driver, both using the `cerber` provider configured for `PHPinnacle\Cerber\Models\User`. Generate the JWT signing secret with `php artisan jwt:secret`. The panel uses the guard supplied by its selected context:

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

The provider list shows the callback URL to register with the OAuth service and the number of OAuth logins linked to each provider. Filament handles its providers through session login; frontend applications use the JWT API. There is no session/JWT switch in the provider form. Both entry points reject a provider assigned to another context. The panel's connection-test action is shown for providers whose context matches the panel; it does not sign in or create an account binding.

Bindings are unique by provider and external account ID, with one account per user and provider. Their context comes from the provider and is not duplicated on the binding. The same external identity can belong to different local users through the separate admin and web providers. Linking or unlinking affects only the selected provider. The panel profile page lists providers for its context. For application-owned account management:

```php
use PHPinnacle\Cerber\Models\Login;

Login::linked($context, $user);
$provider->link($user, $verifiedSocialiteUser);
$provider->unlink($user);
```

`linked()` returns provider IDs. `Provider::unlink()` deletes the local binding; it does not revoke tokens at the external provider. Only pass identities obtained through a verified OAuth flow to `Provider::link()`, and authorize the target user and provider context in the calling application. An external identity already bound to another user through the same provider cannot be reassigned by linking.

## Password authentication API

Enable the password authentication routes in `config/phpinnacle-cerber.php`:

```php
'user_model' => App\Models\User::class,
'api' => [
    'enabled' => true,
    'frontend_url' => env('CERBER_API_FRONTEND_URL'),
    'callback_url' => env('CERBER_API_CALLBACK_URL'),
],
```

Set `CERBER_API_FRONTEND_URL` to the frontend base URL for verification and password recovery pages. `CERBER_API_CALLBACK_URL` is the separate OAuth callback page described below.

Configure the `api` guard with the `jwt` driver and a `cerber` user provider, as described above, and generate the signing secret with `php artisan jwt:secret`. The provider's `model` must match `phpinnacle-cerber.user_model`. The default Laravel password broker must use the same provider. When Cerber uses a separate database connection, set that broker's `connection` to the same configured connection name so its reset tokens use the Cerber database.

| Method | URL | Input |
| --- | --- | --- |
| POST | `/api/auth/register` | `email`, `password` |
| POST | `/api/auth/login` | `email`, `password` |
| GET | `/api/auth/verify-email` | The complete query string from the verification email |
| POST | `/api/auth/password/forgot` | `email` |
| POST | `/api/auth/password/reset` | `email`, `token`, `password` |
| POST | `/api/auth/password/change` | Bearer token; `current`, `password` |
| POST | `/api/auth/refresh` | Bearer token |
| POST | `/api/auth/logout` | Bearer token |
| GET | `/api/profile` | Bearer token |

Route names use the `cerber.api.` prefix: `register`, `login`, `verify-email`, `password.forgot`, `password.reset`, `password.change`, `refresh`, `logout`, and `profile`. Password API routes are disabled by default; OAuth routes remain independently available. Remove application-owned routes for these URLs when enabling Cerber's routes.

Registration creates a user of the configured `user_model` with `panels: ['web']`, a shared password and an unverified email login in one transaction. Email uniqueness uses canonical local email logins. Registration, password reset and password change accept a `password` string of at least eight characters without `password_confirmation`; confirmation belongs to the frontend form. Registration returns HTTP 201 and a JWT immediately; subsequent password sign-in requires email verification. Applications needing additional registration eligibility rules should supply their own registration endpoint instead of enabling this complete public API.

Verification mail goes to the new login after commit. The link opens `{frontend_url}/auth/verify-email`; the frontend must forward **all** query parameters unchanged to `/api/auth/verify-email`. The signed query identifies a login, expires after one hour and becomes invalid if its email changes. Successful verification returns the user profile.

Password recovery sends Laravel's reset notification to the user's default email, with a link to `{frontend_url}/auth/reset-password`. Requests using any registered email resolve the same user. Missing accounts and throttled mail requests receive the same HTTP 200 response as successful requests. Reset tokens follow Laravel's configured expiry and are consumed after a successful reset; invalid tokens return HTTP 422. The notifications use Filament's queued mail notifications and the application's mail/queue configuration.

Authenticated users can change their shared password through `/api/auth/password/change`. The `current` password is required and checked when one exists; passwordless users can set their first password without it. Success returns HTTP 204; invalid input returns HTTP 422. Password changes rotate the user's remember token, preserve existing JWTs until expiry or logout, and leave the administrative `is_password_temporary` marker unchanged.

Registration, login, refresh and OAuth callbacks return `access_token`, `token_type: bearer`, `expires_in` in seconds, `prerequisites`, and `user`. `prerequisites` is a list of string values from `PHPinnacle\Cerber\Enums\Prerequisite`: `['password']` (`Prerequisite::Password`) when `is_password_temporary` is set, otherwise `[]`. This marker does not block API authentication.

The shared `user` profile contains `id`, `name`, `status`, `email`, `email_verified_at` and `created_at`; email fields describe the default email login and can be null. `/api/profile` returns this profile directly.

Bearer-protected routes in this API use `auth:api`. Refresh requires an unexpired bearer token and rotates it; logout returns HTTP 204 and invalidates the token when JWT blacklisting is enabled. Login, registration and password endpoints allow six requests per minute; verification allows ten. Blocked and archived users cannot authenticate through the `cerber` provider, including with previously issued bearer tokens.

## OpenAPI documentation

Cerber ships `OpenApi\Spec` attributes for `swagger-php` 7 on the password API controllers and auth
resources, with shared components in `src/OpenApi`. Add the installed package's
`src` directory to the application's scanner sources. For L5-Swagger:

```php
'annotations' => [
    base_path('app'),
    base_path('vendor/phpinnacle/cerber/src'),
],

// In defaults.scanOptions:
'mode' => 'spec',
```

The application supplies its own `Info` and `Server` attributes. For direct
generation, use `OpenApi\Builder` with the package source and those metadata
classes as sources. Cerber paths are
relative to an `/api` server URL: `/auth/register`, `/auth/login`, and `/profile`,
for example. Authentication schemas and responses use a `Cerber` prefix, and the
JWT security scheme is `cerberBearerAuth`, so they do not depend on application
components. Remove application-owned descriptions for these same operations
before scanning the package to avoid duplicate paths.

## Frontend OAuth JWT flow

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
4. The response contains `access_token`, `token_type`, `expires_in` in seconds, the `prerequisites` list, and the shared `user` profile described above. It does not sign in to a panel session.

Attempts expire after ten minutes and can be exchanged once. The callback verifies the context, provider, callback URL, and browser verifier before contacting the provider.

The cache store must persist attempts between requests and support atomic locks. Workers serving the OAuth flow must share that store.

OAuth-issued JWTs include the `social_account_context` claim. Protect application routes that require an OAuth context with the context middleware so tokens cannot be reused in a different context that shares the signing key and user model:

```php
Route::middleware('cerber.context:web')->group(function () {
    Route::get('/me', fn () => auth()->user());
});
```

`DELETE .../link` requires a bearer token issued for the selected context and removes only the authenticated user's binding in that context. Tokens from password registration or login omit `social_account_context` and cannot access context-protected routes or this unlink endpoint. Provider secrets, access tokens, and browser verifiers are credentials; do not log or expose them.

## Sanctum integration

The service provider registers `Models\AccessToken` as Sanctum's token model, and the migrations create its `access_tokens` table. The bundled `User` model does not include Sanctum's `HasApiTokens` trait or token-management methods. Applications using personal access tokens must supply that user integration. Cerber does not provide personal access token issuance, revocation endpoints, or a token-management page; its password and OAuth APIs issue JWTs.

## Schema and authentication changes

The current schema is for fresh installations. OAuth credentials are stored in `logins`; there is no separate `social_accounts` table. Existing databases and authenticated sessions are not converted automatically. Rewriting published migrations does not update an existing database; apply the application's chosen schema and data procedure before serving the new code. Database resets require explicit authorization.

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
