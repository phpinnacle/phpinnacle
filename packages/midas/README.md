# Midas for Filament

[![Latest Version on Packagist](https://img.shields.io/packagist/v/phpinnacle/midas.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/midas)

Midas is a payment orchestration package for Laravel and Filament. It models payment methods, installment plans, stored cards, payment intents and adjustments behind provider contracts, while supplying Filament resources for operational configuration.

## Features

- Transaction, payment method and payment plan Filament resources.
- Built-in cash, bank, card, ERIP, Stripe, BePaid and WebPay providers.
- `PaymentGateway` and `PaymentProvider` contracts for custom integrations.
- Payment intents, line items, payers, sources and continuation statuses.
- Persisted payment transactions with authorization, capture, void and refund operations.
- Queued execution with atomic scheduling, encrypted requests and provider idempotency.
- Discounts, shipping, tax and fee adjustments.
- Single and multipart payment schemes with validation.
- Tokenized and encrypted card instruments.
- Configurable connection, navigation and tenancy.

## Requirements and installation

- PHP 8.4 or later
- Laravel 13 and Filament 4 or 5
- Laravel Cashier 16
- `phpinnacle/common`

```bash
composer require phpinnacle/midas
php artisan vendor:publish --tag="phpinnacle-midas-config"
php artisan vendor:publish --tag="phpinnacle-midas-migrations"
php artisan migrate
```

## Registering providers

```php
use PHPinnacle\Midas\MidasPlugin;

$panel->plugin(MidasPlugin::make());
```

Register provider class names in `phpinnacle-midas.providers`. The built-in providers are enabled by default; replace the list to restrict availability or add custom providers. The same configuration applies to checkout and queue workers. Keep provider implementations stateless, with merchant credentials in payment method settings.

Registered providers are available when creating payment methods in the panel. Each supplies its label, configuration form, validation and supported payment operations. Stripe supports hosted Checkout payments and refunds, as well as direct PaymentIntent authorizations, captures and voids.

`PaymentGateway::schema($method, $payer)` returns an `Illuminate\JsonSchema\Types\Type`, or `null` when no checkout input is needed. BePaid accepts either new card details or an available saved card. Use Eidos's `RuleFactory` to validate checkout input:

```php
use PHPinnacle\Eidos\Validation\RuleFactory;

$schema = $gateway->schema($method, $payer);
$rules = $schema === null ? [] : app(RuleFactory::class)->make($schema, [], 'payment');
```

## Payment plans and calculations

`PaymentPlan::scheme()` creates a `PaymentScheme` for a price and optional sale date. `PaymentScheme` exposes its start, expiry, total and installment count. Each `IntentLine::total()` multiplies its unit price by quantity. `Intent::total()` sums those line totals and applies typed `Adjustment` objects in order:

```php
use PHPinnacle\Midas\Models\Adjustment;

$adjustment = Adjustment::discount('Campaign', $discount);
$newTotal = $adjustment->apply($subtotal);
```

Gateway results use `Continuation` and `TransactionStatus` to represent success, failure, pending and cancellation without coupling application flows to one provider.

The `PaymentParts` validation rule accepts installment percentages from 1 to 100 that total exactly 100, and integer delays from 0 to 365 days. The `PaymentScheme` rule validates dates and amounts, requires a common currency, and optionally checks the total against an expected amount. These rules validate form input before constructing domain objects.

`PaymentMethod::get($id)` retrieves an active method by ID; passing a provider object instead retrieves the single active method for its key. `PaymentMethod::default($online)` and `list($online)` optionally filter online or offline methods. `MethodSelect::make()->online(false)->withDefault()` offers active offline methods and preselects their default.

## Provider responses and stored cards

Online providers report payment status, provider references and checkout details through `Continuation`. Transport failures and malformed responses raise exceptions; they do not mean that a payment was declined. WebPay rejects incomplete or incorrectly signed notifications.

Configure ERIP checkout instructions in the payment method's `settings['instructions']`, with one instruction per line. Nonempty lines appear in the provider's payment instructions.

BePaid can save a returned card token for its payment method and payer. Repeated responses reuse the saved card. A response without a token does not save a card. Saved cards must be active, belong to the selected method and payer, and remain valid through the end of their expiry month. Invalid provider expiry data is rejected. Concurrent saves of the same new token are not guaranteed to be deduplicated.

## Transactions

### Filament transaction resource

`MidasPlugin` registers `TransactionResource` at `transactions`. Its journal lists payments and child operations with amounts, payment methods, operation types and statuses. Search by number or provider reference, and filter by method (including inactive methods), type, status and dates. Configure the navigation icon and order with `phpinnacle-midas.navigation.transaction`.

Transaction badges show the operation type and status, with their labels, colors and icons.

The view page shows operation details, source/payer references, confirmed captured/refunded/net totals and dates. Its child-operations table links to each operation's own page, including refunds below captures, and children link back to their parent. Unregistered source and payer types show their stored identifier and type. Applications can register supported model types with a renderer that receives the full transaction and returns a Filament schema:

```php
use App\Models\Order;
use App\Filament\Resources\OrderResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Text;
use PHPinnacle\Midas\Contracts\TransactionSource;
use PHPinnacle\Midas\MidasPlugin;
use PHPinnacle\Midas\Models\Transaction;

final class OrderTransactionSource implements TransactionSource
{
    public function type(): string
    {
        return new Order()->getMorphClass();
    }

    /** @return array<TextEntry|Text> */
    public function render(Transaction $transaction): array
    {
        $order = Order::query()->find($transaction->source_id);

        if ($order === null || ! OrderResource::canEdit($order)) {
            return [];
        }

        return [
            TextEntry::make('source_order')
                ->label('Order')
                ->state($order->number)
                ->helperText($order->customer?->name)
                ->url(OrderResource::getUrl('edit', ['record' => $order])),
            Text::make('Transaction ' . $transaction->number),
        ];
    }
}

$panel->plugin(MidasPlugin::make()->sources(new OrderTransactionSource));
```

Sources implement `PHPinnacle\Midas\Contracts\TransactionSource`; payers implement `PHPinnacle\Midas\Contracts\PaymentPayer`. Register payers with `->payers(new CustomerPaymentPayer)`. Each implementation returns the stored reference type from `type()` and Filament components from `render(Transaction $transaction): array`. Use the model's `getMorphClass()` for morph-map aliases. A payer renderer can look up its record with `$transaction->payer_id`. Return `[]` when a reference should not be displayed.

Reference renderers must enforce the current user's access and tenant restrictions. Keep them stateless and create components for the current transaction. Payments and their operations are created through `PaymentManager`; the transaction resource offers no direct creation, editing or deletion of ledger rows.

Standard Filament `viewAny` and `view` policies apply. The resource retains Eloquent global scopes and Filament's default tenant scoping. Applications must supply their access policy and row scope; a `viewAny` policy alone does not filter rows. In a tenant panel, configure the transaction's tenant relationship or an application-owned scope through the usual Filament integration. Midas does not infer tenant ownership from arbitrary source or payer references.

### Transaction history entry

`PHPinnacle\Midas\Infolists\TransactionHistoryEntry` displays payment roots and their capture, void, and refund operations in a Filament schema. Supply either a named Eloquent relationship on the current record or a collection/array of `Transaction` models through `state()`:

```php
use PHPinnacle\Midas\Infolists\TransactionHistoryEntry;

TransactionHistoryEntry::make('payment_history')
    ->relationship('paymentTransactions')
    ->limit(20);

TransactionHistoryEntry::make('payment_history')
    ->state($transactions);
```

Each payment is displayed in a card by default. Use `contained(false)` when the entry sits inside an existing section to remove the nested cards and their horizontal padding. The option also accepts a closure with Filament utility injection. History labels, action dialogs, and notifications support English and Russian.

For a custom Filament theme using Tailwind CSS v4, add `@source '../../../../vendor/phpinnacle/midas/resources/views/**/*.blade.php';` to the theme CSS, adjusting the relative path to your project.

The relationship should return the source's Midas transactions, for example a `morphMany(Transaction::class, 'source')` relation. Raw state should contain payment roots; child operations are loaded from their relationships. The entry shows ten roots by default and only records authorized by the transaction resource's `viewAny` and `view` policies. `manageWhen(true)` enables eligible capture, void, refund, and cancellation actions for users allowed to `update` the transaction; it defaults to false. Applications should pass an authorization callback when access also depends on the current record. Offline pending transactions can be canceled locally. The entry commits manual capture, void, and refund records together with their successful confirmation, so listeners observe the completed operation. Online operations require a provider with the corresponding capability.

### Related transaction page

Extend `PHPinnacle\Midas\Pages\ManageTransactions` to add a transaction table to a payer or source resource. The owner model supplies a `paymentTransactions()` relationship; use the `payer` morph for a payer and the `source` morph for a source. The page reuses the Midas transaction table, including filters and links to transaction details. Access requires both permission to view the owner record and transaction `viewAny` permission.

```php
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use PHPinnacle\Midas\Models\Transaction;
use PHPinnacle\Midas\Pages\ManageTransactions;

class Payer extends Model
{
    public function paymentTransactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'payer');
    }
}

final class ManagePayerTransactions extends ManageTransactions
{
    protected static string $resource = PayerResource::class;
}
```

Register `'transactions' => ManagePayerTransactions::route('/{record}/transactions')` in `PayerResource::getPages()` and add the page to the resource's record sub-navigation to expose it beside the payer's edit page. The table is read-only; Midas processes payments and operations through its domain API.

### Transaction model

`PHPinnacle\Midas\Models\Transaction` represents a payment or one of its operations. Captures and voids belong to an authorization; refunds belong to the payment or capture being refunded. `root()` returns the initial payment or authorization. Use `Intent` to describe a new payment.

Each operation has an amount, currency, type, status, provider reference and metadata. Its source and payer can reference application models or morph-map aliases. Source and payer model keys must be UUIDs.

`status` uses `TransactionStatus` and records the state of that record's operation: `pending`, `success`, `failure` or `cancel`. `type` identifies the operation: payment, authorization, capture, void or refund. Child operations do not change their parent's status: a successful authorization stays successful after capture or void, and a successful payment or capture stays successful after a refund. A successful refund also has `status = success`. Read `captured()`, `refunded()`, `received()` and `capturable()` for amounts; pending reservations do not mean that funds have been captured.

Payment methods and parent operations cannot be deleted while transactions refer to them.

Use `PaymentManager` to persist an operation and schedule its execution atomically:

```php
use PHPinnacle\Midas\Services\PaymentManager;

$payments = app(PaymentManager::class);
$transaction = $payments->payment($intent);

// The response is pending. Read the refreshed transaction after the worker runs.
```

For checkout flows that need an immediate provider response, call `$payments->paymentNow($intent)`. It creates the transaction, invokes the synchronous gateway API for an online method and applies the result before returning. This also supports ERIP and WebPay, which do not implement `QueuedGateway`. Offline methods remain pending without invoking a provider. Unlike `payment()`, this method does not enqueue a job or provide durable replay; a transport exception propagates and leaves the saved operation pending. Use `payment()` for queued execution.

For cash, a bank transfer or an external terminal, the manager creates a pending transaction without a provider job. Call `$transaction->confirmManual()` only after an authorized operator or trusted integration confirms receipt of funds. Pass an optional `DateTimeInterface` completion date and metadata with `$transaction->confirmManual($processedAt, ['receipt' => 'RECEIPT-1'])`; omission uses the current time. This method confirms offline payments and child operations, merging metadata and saving the status, date and metadata in one transition before dispatching events. It rejects online methods and leaves terminal operations unchanged on repeated calls. Bank transfer initialization does not confirm receipt. The application authorizes operations and scopes access to transactions; Midas performs supported queued requests in the worker.

`AuthorizationGateway` exposes `authorize()`, `capture()` and `void()`. `RefundGateway` exposes `refund()`. BePaid and Stripe implement both. Other providers retain their existing `PaymentGateway` contract and must not be assumed to support these operations. Check the corresponding interface before offering an online operation.

```php
use PHPinnacle\Money\Money;

// Use a fresh Intent ID. This example authorizes USD 10.00.
$authorization = $payments->authorize($intent);

// In a later request, once the authorization reaches TransactionStatus::Success:
$capture = $payments->capture($authorization, 'CAP-001', new Money(600, 'USD'));

// Once capture succeeds, refund its funds:
$refund = $payments->refund($capture, 'REF-001', new Money(200, 'USD'), 'Returned item');

// First confirm that the provider still holds the remainder and supports void.
$remaining = $authorization->refresh()->capturable();

if (!$remaining->isZero()) {
    $void = $payments->void($authorization, 'VOID-001', $remaining);
}
```

The manager's `capture()`, `void()` and `refund()` create pending operations and queue online execution. Pending and successful operations consume the available amount; failure or cancellation releases reservations. Amounts must be positive and use the parent's currency. `capturable()` and `refundable()` report available amounts, while provider capabilities and limits still apply. Check the provider's current hold before voiding a remainder. Refresh a loaded parent after a child result. The corresponding `Transaction` methods persist an operation without scheduling provider execution.

`AuthorizationGateway` implementations must declare `amountMode(TransactionType $operation): AmountMode` for `CAPTURE` and `VOID`, using `PHPinnacle\Midas\Enums\AmountMode`. `Partial` permits any amount within the available balance; `Full` requires the original authorization amount. Stripe declares `Full` for both operations, while BePaid declares `Partial`. The manager enforces this policy before queueing. The history form fixes the amount for `Full` and hides the operation when reservations leave insufficient funds. Refunds and offline operations remain partial. Custom gateways must also enforce their mode in direct `capture()` and `void()` calls; `AmountMode::validate($amount, $authorized)` provides the shared check.

A connection failure leaves the saved operation pending because its remote outcome is unknown. Reconcile with the provider or retry the same operation, using its existing ID; do not create a replacement operation merely because an HTTP response was lost. BePaid uses the operation ID as `RequestID`. Gateway requests should run after the transaction record has committed, outside a surrounding database transaction that could roll back after money has moved.

Midas registers `POST /api/midas/transactions/{id}/notify` as `midas.transaction.notify`. Use `route('midas.transaction.notify', ['id' => $intent->id])` for BePaid, ERIP and WebPay notification URLs. BePaid and ERIP notifications must pass merchant HTTP Basic credentials and match the operation's tracking ID, provider ID, amount and currency. WebPay notifications must have a valid provider signature and match the operation number and provider ID. Midas queues `PaymentManager::synchronize()` for BePaid after verification; the job retrieves the current provider state, so delayed webhook snapshots cannot overwrite a newer result. ERIP and WebPay apply their verified notifications directly. An expiry timestamp alone is not evidence that a delayed success should be discarded. Custom providers can implement `WebhookGateway` to use this route. Stripe webhook authentication and correlation are the application's responsibility.

`Transaction::handle($continuation)` applies an operation result, merges metadata, retains omitted provider references and expiry dates, and records completion in `processed_at`. Duplicate or late results do not change a terminal operation. The root completion date belongs to the initial payment or authorization, not later refunds. Sensitive card input and raw provider responses are not stored in transaction metadata.

For custom provider reconciliation, `Transaction::synchronize($continuation, $expectedVersion)` can correct a terminal status. Read `$transaction->root()->refresh()->version` before retrieving provider state and pass it with the result. A concurrent change raises `StaleTransaction`; retrieve fresh provider state before trying again. A return to pending clears `processed_at` and reserves the amount again. Synchronize capture and refund outcomes on their corresponding child records. Applications using `PaymentManager::synchronize()` do not need to manage versions themselves.

`captured()`, `refunded()` and `received()` return confirmed totals, including refunds below capture children. These totals are calculated from the records, so reservations and duplicate responses cannot increase them. `received()` is captured funds less successful refunds. Query `Transaction::forSource($source)->whereNull('parent_id')` for payment roots and their totals.

`balanceImpact()` instead returns one operation's effect on funds received: a successful payment or capture is positive, a successful refund is negative, and authorizations, voids and unfinished or failed operations contribute zero. It uses the operation's `type` and `status`: refunding a capture does not erase the original capture's positive effect. Summing operation effects through `Transaction::forSource($source)` counts each effect once; do not also add root aggregate totals. The application owns payment allocation, installments, order status changes and business events.

Midas emits `TransactionCreated`, `TransactionUpdated` and `TransactionStatusChanged` after the database transaction commits. A rollback emits no events. Creation and update events carry `$transaction`; status changes also carry `$previousStatus` and `$status`. Changed checkout details can emit an update while the status remains pending. Repeating an unchanged result emits no new update or status event. Register ordinary Laravel listeners, for example:

```php
use Illuminate\Support\Facades\Event;
use PHPinnacle\Midas\Events\TransactionStatusChanged;

Event::listen(TransactionStatusChanged::class, ReconcilePayment::class);
```

In the listener, reconcile the allocation with the payment root's current `received()` total. Replace the recorded contribution idempotently instead of adding the whole amount again; reconciliation can reverse an earlier result. A refund changes the balance without changing the root's operation status, so handle status events from child operations as well as roots. Scope transaction lookups through an authorized source or payment method, including the application's tenant boundary; this table does not install a global tenant scope or infer a current tenant.

### Optional WebSocket updates

Register Midas's broadcast listener in the application to publish committed creation and update events. The application supplies its Laravel broadcaster and queue; Midas does not require a specific WebSocket server.

```php
use Illuminate\Support\Facades\Event;
use PHPinnacle\Midas\Events\TransactionCreated;
use PHPinnacle\Midas\Events\TransactionUpdated;
use PHPinnacle\Midas\Listeners\BroadcastTransaction;

Event::listen([TransactionCreated::class, TransactionUpdated::class], BroadcastTransaction::class);
```

Messages use the private `midas.transactions.{rootId}` channel and the `midas.transaction.changed` event. The payload contains `root` with identity, status, version and confirmed/available amounts, and `operation` with its details and checkout information. Amounts are integer minor units. Card data, raw provider responses and arbitrary metadata are excluded.

Authorize subscriptions in the application's `routes/channels.php` using the same tenant and transaction access rules as its HTTP interface. For example, an application policy that grants `view` to both permitted operators and authenticated payers can be used for the channel:

```php
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Gate;
use PHPinnacle\Midas\Models\Transaction;

Broadcast::channel('midas.transactions.{rootId}', function (Authenticatable $user, string $rootId): bool {
    $root = Transaction::query()->whereNull('parent_id')->find($rootId);

    return $root !== null && Gate::forUser($user)->allows('view', $root);
});
```

Subscribe with `Echo.private('midas.transactions.' + rootId).listen('.midas.transaction.changed', handler)`. The initial page state still comes from the application's authorized read path; subsequent messages supply the updated operation and root balance without a request per message. Compare `root.version` when applying messages that may arrive out of order. Refresh once after reconnecting to recover any missed messages. Broadcasting runs through Laravel's queue and failures are reported without changing the payment outcome.

### Queue setup and recovery

Queued execution requires Laravel's database queue on the **same database connection** as Midas. Payment creation and scheduling succeed or roll back together; workers execute only committed operations. Other queue drivers and database connections are rejected for this flow.

`phpinnacle-midas.connection` selects the database for Midas records and migrations; `null` uses the application default. Keep that connection consistent between HTTP requests and workers, including while payments are queued.

For example, configure a queue connection in `config/queue.php`:

```php
'connections' => [
    'midas' => [
        'driver' => 'database',
        'connection' => 'mysql',
        'table' => 'jobs',
        'queue' => 'payments',
        'retry_after' => 150,
        'after_commit' => false,
    ],
],
```

And select it in `config/phpinnacle-midas.php`:

```php
'connection' => 'mysql',
'queue' => ['connection' => 'midas', 'queue' => 'payments'],
```

Create Laravel's standard `jobs` and `failed_jobs` tables on that database if your application does not already have them, then run its migrations. Start the worker with:

```bash
php artisan queue:work midas --queue=payments
php artisan queue:failed
php artisan queue:retry <failed-job-uuid>
```

The default queue connection is `database`, with queue name `payments`. Use a shared Laravel cache with atomic locks across workers. Set `retry_after` above the execution timeout and overlap protection interval; 150 seconds is suitable for the current defaults. Operations on the same payment are coordinated to prevent overlapping balance changes and stale provider results.

Laravel owns job reservation, retries, failure storage and redelivery after a worker crash. A failed job does **not** mean a declined payment: its remote outcome may be unknown, so the transaction stays pending and its amount remains reserved. Jobs make up to five attempts with backoff. Inspect failed jobs and reconcile or retry the existing operation; do not create a new payment ID because of a timeout. A pending provider response completes the execution job, but leaves the payment pending. Authenticated webhooks or an application reconciliation task must request subsequent synchronization.

Custom queued providers implement `QueuedGateway`. `prepare(Intent $intent)` and `derive(Transaction $transaction)` return a `GatewayRequest` without contacting the provider. `execute()` performs that request using the operation ID as its idempotency key. `synchronize()` retrieves the current state of that operation. Return the request's replay deadline and any checkout metadata with `GatewayRequest` so retries can preserve the original payment terms. Both BePaid and Stripe support this contract; ERIP and WebPay use synchronous execution.

Stripe requires an `Intent` with a `returnUrl`, no instrument and `recurring: false`. For a payment, the worker creates a [Checkout Session](https://docs.stripe.com/api/checkout/sessions/create). Its URL appears in transaction metadata as `redirect` and in the optional WebSocket update as `redirect_url`; the application redirects the payer there. A completed Checkout Session can still be unpaid, so only Stripe's `paid` payment status confirms a payment. A failed delayed PaymentIntent fails the operation.

For an authorization, the worker creates a direct card [PaymentIntent](https://docs.stripe.com/api/payment_intents/create) with `capture_method=manual`. Its identifier is the transaction's `external_id`, and its `client_secret` appears in transaction metadata. The application must show Stripe's [Payment Element](https://docs.stripe.com/payments/payment-element/migration) to the authorized payer using the payment method's `public_key`, then confirm that PaymentIntent with Stripe.js and the intent's return URL. Treat the client secret as payer-specific data. Synchronization confirms the hold when the PaymentIntent reaches `requires_capture`. Captures and voids must use the full authorized amount; [ordinary partial capture releases the remainder](https://docs.stripe.com/api/payment_intents/capture), which cannot be represented as a remaining Midas hold. A capture can then be refunded. Stripe controls the authorization window; Midas does not record a hold expiry from the PaymentIntent.

Authenticate Stripe webhook notifications in the application and call `$payments->synchronize($transaction)` for the matching payment, authorization, capture, void or refund. Midas retrieves the current Stripe object instead of applying an untrusted webhook snapshot. Checkout Sessions that expire without payment fail the operation. Midas does not support Stripe subscriptions. Keep a payment method bound to the same Stripe account while operations are pending. Stripe requests use the transaction UUID as an [idempotency key](https://docs.stripe.com/api/idempotent_requests), with the same 23-hour replay deadline as BePaid. If a response is lost and no remote ID was saved, inspect Stripe's request logs using that UUID before replaying after the deadline.

BePaid freezes the request, including expiry and the saved-card token or encrypted card details, before enqueueing. It sends the transaction UUID as `RequestID` and stops replaying after 23 hours, within the provider's [24-hour idempotency retention](https://docs.bepaid.by/en/using_api/idempotent_requests/). Retrying an expired job does not extend this deadline. If the external identifier was lost, reconcile the provider operation by its tracking ID before applying an authoritative result; never blindly resend after retention expires. The [status query API](https://docs.bepaid.by/en/integration/card_api/transactions/status_query/) is used for synchronization by external ID. Keep a payment method tied to the same merchant account while its operations are pending.

Queued BePaid payment and authorization accept `EncryptedCard` or an active saved `CardToken` for the same payer and payment method. Saved tokens with CVC are unsupported. With `EncryptedCard::persist`, a successful payment or later reconciliation saves the returned card token. Queued card input is encrypted, including failed jobs retained for retry. Keep the application encryption key available to workers; do not change merchant credentials while related operations are pending.

## Testing

Tests cover amounts, reservations, status transitions, installment schedules, saved-card ownership, access policies, provider failures, and checkout, webhook, queue and broadcast outcomes.

```bash
composer test
```

## Changelog and license

See [CHANGELOG](CHANGELOG.md). Released under the [MIT License](LICENSE.md).
