<?php

use Filament\Actions\Action;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Infolists\Components\TextEntry;
use Filament\Panel;
use Filament\PanelRegistry;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Illuminate\Auth\Access\Gate as LaravelGate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPinnacle\Midas\Contracts\PaymentPayer;
use PHPinnacle\Midas\Contracts\TransactionSource;
use PHPinnacle\Midas\Enums\AmountMode;
use PHPinnacle\Midas\Enums\TransactionStatus;
use PHPinnacle\Midas\Enums\TransactionType;
use PHPinnacle\Midas\Events\TransactionCreated;
use PHPinnacle\Midas\Infolists\TransactionHistoryEntry;
use PHPinnacle\Midas\MidasPlugin;
use PHPinnacle\Midas\Models\Continuation;
use PHPinnacle\Midas\Models\Intent;
use PHPinnacle\Midas\Models\IntentLine;
use PHPinnacle\Midas\Models\Payer;
use PHPinnacle\Midas\Models\PaymentMethod;
use PHPinnacle\Midas\Models\Source;
use PHPinnacle\Midas\Models\Transaction;
use PHPinnacle\Midas\Pages\ManageTransactions;
use PHPinnacle\Midas\Payments\BePaid;
use PHPinnacle\Midas\Payments\Cash;
use PHPinnacle\Midas\Resources\Methods\Pages\ListMethods;
use PHPinnacle\Midas\Resources\Transactions\Pages\ListTransactions;
use PHPinnacle\Midas\Resources\Transactions\Pages\ViewTransaction;
use PHPinnacle\Midas\Resources\Transactions\RelationManagers\OperationsRelationManager;
use PHPinnacle\Midas\Resources\Transactions\TransactionResource;
use PHPinnacle\Midas\Services\PaymentManager;
use PHPinnacle\Midas\Services\ProviderRegistry;
use PHPinnacle\Money\Money;
use Tests\TestCase;

uses(TestCase::class);

class MidasAmountProvider extends BePaid
{
    public function key(): string
    {
        return 'amount-provider';
    }

    public function amountMode(TransactionType $operation): AmountMode
    {
        return $operation === TransactionType::CAPTURE ? AmountMode::Full : AmountMode::Partial;
    }
}

function midas_amount_authorization(): Transaction
{
    $provider = new MidasAmountProvider;
    app(ProviderRegistry::class)->register($provider);
    $method = $provider->define();
    $method->save();

    return Transaction::authorize(new Intent(
        id: (string) Str::uuid(),
        number: 'AUTH-AMOUNT',
        description: 'Authorization amount policy',
        method: $method,
        source: new Source('00000000-0000-0000-0000-000000000001', 'order'),
        payer: new Payer('00000000-0000-0000-0000-000000000002', 'customer'),
        instrument: null,
        lines: [new IntentLine('Item', 1, new Money(1000, 'USD'))],
    ))->handle(Continuation::success('authorization'));
}

class MidasTransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->getAttribute('can_view_transactions');
    }

    public function view(User $user, Transaction $transaction): bool
    {
        return $this->viewAny($user) && $transaction->number !== 'DENIED';
    }

    public function update(User $user, Transaction $transaction): bool
    {
        return $user->getAttribute('can_manage_transactions');
    }
}

class MidasResourceSource implements TransactionSource
{
    public function type(): string
    {
        return new PaymentMethod()->getMorphClass();
    }

    /** @return array<TextEntry|Text> */
    public function render(Transaction $transaction): array
    {
        $method = PaymentMethod::query()->find($transaction->source_id);

        return (
            $method === null
                ? []
                : [
                    TextEntry::make('source_title')
                        ->label('Source')
                        ->state('Source ' . $method->name)
                        ->helperText('Provider: ' . $method->provider)
                        ->url('/sources/' . $method->id),
                    Text::make('Source operation ' . $transaction->number),
                ]
        );
    }
}

class MidasResourcePayer implements PaymentPayer
{
    public function type(): string
    {
        return new PaymentMethod()->getMorphClass();
    }

    /** @return array<TextEntry|Text> */
    public function render(Transaction $transaction): array
    {
        $method = PaymentMethod::query()->find($transaction->payer_id);

        return (
            $method === null
                ? []
                : [
                    TextEntry::make('payer_title')
                        ->label('Payer')
                        ->state('Payer ' . $method->name)
                        ->helperText('Payment method')
                        ->url('/payers/' . $method->id),
                    Text::make('Payer operation ' . $transaction->number),
                ]
        );
    }
}

class MidasHistorySource extends PaymentMethod
{
    /** @return MorphMany<Transaction, $this> */
    public function paymentTransactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'source');
    }
}

class MidasHistoryPayer extends PaymentMethod
{
    /** @return MorphMany<Transaction, $this> */
    public function paymentTransactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'payer');
    }
}

class MidasHistoryPayerResource extends Resource
{
    protected static ?string $model = MidasHistoryPayer::class;

    public static function getPages(): array
    {
        return [
            'index' => MidasHistoryPayerList::route('/'),
            'transactions' => MidasTestManageTransactions::route('/{record}/transactions'),
        ];
    }
}

class MidasHistoryPayerList extends ListRecords
{
    protected static string $resource = MidasHistoryPayerResource::class;
}

class MidasTestManageTransactions extends ManageTransactions
{
    protected static string $resource = MidasHistoryPayerResource::class;
}

class MidasHistoryPayerPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MidasHistoryPayer $payer): bool
    {
        return $payer->name !== 'Blocked';
    }
}

function midas_resource_payment(string $number = 'PAY-001', ?Source $source = null, ?Payer $payer = null): Transaction
{
    $method = new Cash()->define();
    $method->save();

    return Transaction::payment(new Intent(
        id: (string) Str::uuid(),
        number: $number,
        description: 'Order payment',
        method: $method,
        source: $source ?? new Source('00000000-0000-0000-0000-000000000001', 'order'),
        payer: $payer ?? new Payer('00000000-0000-0000-0000-000000000002', 'customer'),
        instrument: null,
        lines: [new IntentLine('Item', 1, new Money(1000, 'USD'))],
    ));
}

beforeEach(function () {
    (require __DIR__ . '/../../database/migrations/create_midas_tables.php')->up();
    Http::preventStrayRequests();
    $panel = Panel::make()
        ->id('midas-test')
        ->path('admin')
        ->default()
        ->homeUrl('/admin')
        ->plugin(MidasPlugin::make())
        ->resources([MidasHistoryPayerResource::class]);
    app(PanelRegistry::class)->register($panel);
    Filament::setCurrentPanel($panel);
    Filament::bootCurrentPanel();
    Route::name('filament.midas-test.')
        ->prefix('admin')
        ->group(function () use ($panel) {
            foreach ($panel->getResources() as $resource) {
                $resource::registerRoutes($panel);
            }
        });
    Route::getRoutes()->refreshNameLookups();
    // Isolate the package from unrelated monorepo authorization callbacks.
    Gate::swap(new LaravelGate(app(), fn () => auth()->user()));
    Gate::policy(Transaction::class, MidasTransactionPolicy::class);
    Gate::policy(MidasHistoryPayer::class, MidasHistoryPayerPolicy::class);
    $this->actingAs(new User()->forceFill([
        'id' => 1,
        'name' => 'Operator',
        'email' => 'operator@example.test',
        'can_view_transactions' => true,
        'can_manage_transactions' => true,
    ]));
});

it('creates payment methods from the registered provider actions', function () {
    $page = Livewire::test(ListMethods::class)->assertSuccessful();
    $action = collect($page->instance()->getTable()->getFlatActions())
        ->sole(fn (Action $action) => $action->getLabel() === new Cash()->getLabel());

    $page
        ->callAction(TestAction::make($action->getName())->table())
        ->assertHasNoFormErrors();

    $method = PaymentMethod::query()->sole();

    expect($method->provider)
        ->toBe('cash')
        ->and($method->name)
        ->toBe(new Cash()->getLabel())
        ->and($method->is_online)
        ->toBeFalse();
});

it('lists and filters payment roots and child operations including inactive methods', function () {
    $payment = midas_resource_payment()->handle(Continuation::success());
    $refund = $payment->refund('REF-001', new Money(200, 'USD'), 'Returned item');
    $other = midas_resource_payment('PAY-OTHER');
    $payment->method->is_active = false;
    $payment->method->save();

    Livewire::test(ListTransactions::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$payment, $refund, $other])
        ->filterTable('method_id', $payment->method_id)
        ->assertCanSeeTableRecords([$payment, $refund])
        ->assertCanNotSeeTableRecords([$other])
        ->filterTable('type', 'refund')
        ->assertCanSeeTableRecords([$refund])
        ->assertCanNotSeeTableRecords([$payment]);

    Livewire::test(ListTransactions::class)
        ->filterTable('status', 'success')
        ->assertCanSeeTableRecords([$payment])
        ->assertCanNotSeeTableRecords([$refund, $other]);

    $refund->handle(Continuation::success());

    Livewire::test(ListTransactions::class)
        ->filterTable('status', 'success')
        ->assertCanSeeTableRecords([$payment, $refund])
        ->assertCanNotSeeTableRecords([$other])
        ->filterTable('type', 'refund')
        ->assertCanSeeTableRecords([$refund])
        ->assertCanNotSeeTableRecords([$payment]);

    Livewire::test(ListTransactions::class)
        ->searchTable('REF-001')
        ->assertCanSeeTableRecords([$refund])
        ->assertCanNotSeeTableRecords([$payment, $other]);
});

it('shows confirmed balances and unregistered application references as identifiers', function () {
    $payment = midas_resource_payment()->handle(Continuation::success());
    $refund = $payment->refund('REF-001', new Money(250, 'USD'), 'Returned item')->handle(Continuation::success());

    Livewire::test(ViewTransaction::class, ['record' => $payment->id])
        ->assertSuccessful()
        ->assertSee('10.00 USD')
        ->assertSee('2.50 USD')
        ->assertSee('7.50 USD')
        ->assertSee('00000000-0000-0000-0000-000000000001')
        ->assertSee('00000000-0000-0000-0000-000000000002')
        ->assertSee('order')
        ->assertSee('customer');

    Livewire::test(ViewTransaction::class, ['record' => $refund->id])
        ->assertSuccessful()
        ->assertSee('Returned item')
        ->assertSee(TransactionResource::getUrl('view', ['record' => $payment]), escape: false);

    Http::assertNothingSent();
});

it('renders registered source and payer schemas and hides unavailable references', function () {
    MidasPlugin::get()
        ->sources(new MidasResourceSource)
        ->payers(new MidasResourcePayer);

    $method = new Cash()->define();
    $method->save();
    $payment = Transaction::payment(new Intent(
        id: (string) Str::uuid(),
        number: 'PAY-REGISTERED',
        description: 'Registered references',
        method: $method,
        source: new Source($method->id, $method->getMorphClass()),
        payer: new Payer($method->id, $method->getMorphClass()),
        instrument: null,
        lines: [new IntentLine('Item', 1, new Money(1000, 'USD'))],
    ));

    Livewire::test(ViewTransaction::class, ['record' => $payment->id])
        ->assertSuccessful()
        ->assertSee('Source ' . $method->name)
        ->assertSee('Provider: cash')
        ->assertSee('/sources/' . $method->id, escape: false)
        ->assertSee('Payer ' . $method->name)
        ->assertSee('Payment method')
        ->assertSee('/payers/' . $method->id, escape: false)
        ->assertSee('Source operation PAY-REGISTERED')
        ->assertSee('Payer operation PAY-REGISTERED')
        ->assertDontSee($method->getMorphClass());

    $missing = Transaction::payment(new Intent(
        id: (string) Str::uuid(),
        number: 'PAY-MISSING-REFERENCES',
        description: 'Missing references',
        method: $method,
        source: new Source('00000000-0000-0000-0000-000000000003', $method->getMorphClass()),
        payer: new Payer('00000000-0000-0000-0000-000000000004', $method->getMorphClass()),
        instrument: null,
        lines: [new IntentLine('Item', 1, new Money(1000, 'USD'))],
    ));

    Livewire::test(ViewTransaction::class, ['record' => $missing->id])
        ->assertSuccessful()
        ->assertDontSee('00000000-0000-0000-0000-000000000003')
        ->assertDontSee('00000000-0000-0000-0000-000000000004')
        ->assertDontSee($method->getMorphClass())
        ->assertDontSee('/sources/00000000-0000-0000-0000-000000000003', escape: false);
});

it('shows only the selected parents children and links them to their view pages', function () {
    $payment = midas_resource_payment()->handle(Continuation::success());
    $refund = $payment->refund('REF-001', new Money(200, 'USD'), 'Returned item');
    $other = midas_resource_payment('PAY-OTHER')->handle(Continuation::success());
    $otherRefund = $other->refund('REF-OTHER', new Money(100, 'USD'), 'Other item');

    Livewire::test(OperationsRelationManager::class, ['ownerRecord' => $payment, 'pageClass' => ViewTransaction::class])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$refund])
        ->assertCanNotSeeTableRecords([$payment, $other, $otherRefund])
        ->assertSee(TransactionResource::getUrl('view', ['record' => $refund]), escape: false);
});

it('honors list and view policies', function () {
    $denied = midas_resource_payment('DENIED');
    Livewire::test(ViewTransaction::class, ['record' => $denied->id])->assertForbidden();

    auth()->user()->setAttribute('can_view_transactions', false);
    Livewire::test(ListTransactions::class)->assertForbidden();
});

it('preserves application query scopes for listing and direct record access', function () {
    $visible = midas_resource_payment();
    $hidden = midas_resource_payment('PAY-HIDDEN');
    Transaction::addGlobalScope('permitted-method', fn (Builder $query) => $query->where(
        'method_id',
        $visible->method_id,
    ));

    Livewire::test(ListTransactions::class)
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
    Livewire::test(ViewTransaction::class, ['record' => $hidden->id])->assertNotFound();
});

it('does not expose direct ledger editing or deletion', function () {
    $payment = midas_resource_payment();

    Livewire::test(ListTransactions::class)
        ->assertTableActionDoesNotExist('edit')
        ->assertTableActionDoesNotExist('delete')
        ->assertTableBulkActionDoesNotExist('delete');

    expect(TransactionResource::canCreate())
        ->toBeFalse()
        ->and(TransactionResource::canEdit($payment))
        ->toBeFalse()
        ->and(TransactionResource::canDelete($payment))
        ->toBeFalse()
        ->and(TransactionResource::canDeleteAny())
        ->toBeFalse();
});

it('reads payment roots through a named transaction relationship', function () {
    $source = new MidasHistorySource;
    $source->name = 'Source';
    $source->provider = 'cash';
    $source->settings = [];
    $source->save();

    $payment = midas_resource_payment('HISTORY-RELATION', new Source($source->id, $source->getMorphClass()))
        ->handle(Continuation::success());
    $refund = $payment->refund('HISTORY-REFUND', new Money(200, 'USD'), 'Returned item');
    midas_resource_payment('HISTORY-OTHER');

    $entry = TransactionHistoryEntry::make('history')
        ->model($source)
        ->relationship('paymentTransactions');

    expect($entry->getTransactions()->pluck('id')->all())->toBe([$payment->id]);
    expect($entry->getOperations($entry->getTransactions()->sole())->pluck('id')->all())->toBe([$refund->id]);
});

it('reads payment roots from transaction models supplied as raw state', function () {
    $payment = midas_resource_payment('HISTORY-STATE')->handle(Continuation::success());
    $refund = $payment->refund('HISTORY-STATE-REFUND', new Money(200, 'USD'), 'Returned item');
    $other = midas_resource_payment('HISTORY-STATE-OTHER');

    $entry = TransactionHistoryEntry::make('history')
        ->state(collect([$payment, $refund]));

    expect($entry->getTransactions()->pluck('id')->all())->toBe([$payment->id]);
    expect($entry->getOperations($entry->getTransactions()->sole())->pluck('id')->all())->toBe([$refund->id]);

    $entry->state([$other]);

    expect($entry->getTransactions()->pluck('id')->all())->toBe([$other->id]);
});

it('records a manual refund from the history and rejects another transaction', function () {
    $payment = midas_resource_payment('HISTORY-ACTION')->handle(Continuation::success());
    $other = midas_resource_payment('HISTORY-OUTSIDE')->handle(Continuation::success());
    $createdStatuses = [];
    Event::listen(TransactionCreated::class, function (TransactionCreated $event) use (&$createdStatuses) {
        $createdStatuses[] = $event->transaction->refresh()->status;
    });
    $entry = TransactionHistoryEntry::make('history')
        ->state([$payment])
        ->manageWhen(true);
    $actions = collect($entry->getDefaultActions())->keyBy(fn ($action) => $action->getName());

    $refund = $actions->get('refund');

    expect(fn () => $refund->evaluate($refund->getActionFunction(), [
        'arguments' => ['transaction' => $other->id],
        'data' => ['amount' => new Money(200, 'USD'), 'reason' => 'Returned item'],
    ]))
        ->toThrow(ModelNotFoundException::class);

    $refund->evaluate($refund->getActionFunction(), [
        'arguments' => ['transaction' => $payment->id],
        'data' => ['amount' => new Money(200, 'USD'), 'reason' => 'Returned item'],
    ]);

    $created = $payment->children()->sole();

    expect($created->status)
        ->toBe(TransactionStatus::Success)
        ->and($created->reason)
        ->toBe('Returned item')
        ->and($payment->received()->amount)
        ->toBe(800)
        ->and($other->children()->count())
        ->toBe(0)
        ->and($createdStatuses)
        ->toBe([TransactionStatus::Success]);
});

it('does not offer or execute operations without transaction update permission', function () {
    $payment = midas_resource_payment('HISTORY-READ-ONLY')->handle(Continuation::success());
    auth()->user()->setAttribute('can_manage_transactions', false);
    $entry = TransactionHistoryEntry::make('history')
        ->state([$payment])
        ->manageWhen(true);
    $refund = collect($entry->getDefaultActions())->first(fn ($action) => $action->getName() === 'refund');

    expect($entry->canOperate($payment, 'refund', app(ProviderRegistry::class)))->toBeFalse();

    $refund->evaluate($refund->getActionFunction(), [
        'arguments' => ['transaction' => $payment->id],
        'data' => ['amount' => new Money(200, 'USD'), 'reason' => 'Returned item'],
    ]);

    expect($payment->children()->count())->toBe(0);
});

it('cancels a pending manual transaction from the history', function () {
    $payment = midas_resource_payment('HISTORY-CANCEL');
    $entry = TransactionHistoryEntry::make('history')
        ->state([$payment])
        ->manageWhen(true);
    $cancel = collect($entry->getDefaultActions())->first(fn ($action) => $action->getName() === 'cancel');

    $cancel->evaluate($cancel->getActionFunction(), ['arguments' => ['transaction' => $payment->id]]);

    expect($payment->refresh()->status)->toBe(TransactionStatus::Cancel);
});

it('records manual capture and void against the same authorization', function () {
    $method = new Cash()->define();
    $method->save();
    $authorization = Transaction::authorize(new Intent(
        id: (string) Str::uuid(),
        number: 'AUTH-001',
        description: 'Order authorization',
        method: $method,
        source: new Source('00000000-0000-0000-0000-000000000001', 'order'),
        payer: new Payer('00000000-0000-0000-0000-000000000002', 'customer'),
        instrument: null,
        lines: [new IntentLine('Item', 1, new Money(1000, 'USD'))],
    ))->handle(Continuation::success());
    $entry = TransactionHistoryEntry::make('history')
        ->state([$authorization])
        ->manageWhen(true);
    $actions = collect($entry->getDefaultActions())->keyBy(fn ($action) => $action->getName());

    foreach (['capture' => 600, 'void' => 400] as $name => $amount) {
        $action = $actions->get($name);
        $action->evaluate($action->getActionFunction(), [
            'arguments' => ['transaction' => $authorization->id],
            'data' => ['amount' => new Money($amount, 'USD')],
        ]);
    }

    expect($authorization->children()->pluck('status', 'type')->all())
        ->toBe([
            'capture' => TransactionStatus::Success,
            'void' => TransactionStatus::Success,
        ])
        ->and($authorization->captured()->amount)
        ->toBe(600)
        ->and($authorization->capturable()->isZero())
        ->toBeTrue();
});

it('uses the providers amount mode for each authorization operation', function () {
    $authorization = midas_amount_authorization();
    $entry = TransactionHistoryEntry::make('history')->state([$authorization])->manageWhen(true);
    $actions = collect($entry->getDefaultActions())->keyBy(fn ($action) => $action->getName());

    $capture = $actions->get('capture')->arguments(['transaction' => $authorization->id]);
    $amount = $capture->getSchema(Schema::make())->getComponents()[0];

    expect($amount->isReadOnly())
        ->toBeTrue()
        ->and($amount->getDefaultState()->amount)
        ->toBe(1000)
        ->and($amount->getEqValue()->amount)
        ->toBe(1000);

    $void = $actions->get('void')->arguments(['transaction' => $authorization->id]);
    $amount = $void->getSchema(Schema::make())->getComponents()[0];

    expect($amount->isReadOnly())->toBeFalse()->and($amount->getMaxValue()->amount)->toBe(1000);

    $reservation = $authorization->capture('RESERVED', new Money(300, 'USD'));
    $providers = app(ProviderRegistry::class);

    expect($entry->canOperate($authorization, 'capture', $providers))
        ->toBeFalse()
        ->and($entry->canOperate($authorization, 'void', $providers))
        ->toBeTrue();

    $amount = $void->getSchema(Schema::make())->getComponents()[0];
    expect($amount->getDefaultState()->amount)->toBe(700)->and($amount->getMaxValue()->amount)->toBe(700);

    $reservation->handle(Continuation::failure('Declined'));
    expect($entry->canOperate($authorization, 'capture', $providers))->toBeTrue();
});

it('enforces a custom providers full amount mode before queueing', function () {
    $authorization = midas_amount_authorization();

    expect(fn () => app(PaymentManager::class)->capture($authorization, 'PARTIAL', new Money(300, 'USD')))
        ->toThrow(InvalidArgumentException::class, 'full capture or void');
    expect($authorization->children()->count())->toBe(0);
    Http::assertNothingSent();
});

it('lists only the payer transactions on a related records page', function () {
    $payer = new MidasHistoryPayer;
    $payer->name = 'Allowed';
    $payer->provider = 'cash';
    $payer->settings = [];
    $payer->save();

    $otherPayer = new MidasHistoryPayer;
    $otherPayer->name = 'Other';
    $otherPayer->provider = 'cash';
    $otherPayer->settings = [];
    $otherPayer->save();

    $payment = midas_resource_payment('PAYER-OWN', payer: new Payer($payer->id, $payer->getMorphClass()));
    $otherPayment = midas_resource_payment(
        'PAYER-OTHER',
        payer: new Payer($otherPayer->id, $otherPayer->getMorphClass()),
    );

    Livewire::test(MidasTestManageTransactions::class, ['record' => $payer->id])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$payment])
        ->assertCanNotSeeTableRecords([$otherPayment]);
});

it('requires access to the owner record before showing its transactions', function () {
    $payer = new MidasHistoryPayer;
    $payer->name = 'Blocked';
    $payer->provider = 'cash';
    $payer->settings = [];
    $payer->save();

    expect(MidasTestManageTransactions::canAccess(['record' => $payer]))->toBeFalse();
});

it('renders Russian payment history labels and operation dialogs', function () {
    app()->setLocale('ru');
    $payment = midas_resource_payment('HISTORY-RUSSIAN')->handle(Continuation::success());
    $entry = TransactionHistoryEntry::make('history')->state([$payment])->manageWhen(true);
    $page = new ViewTransaction;
    $page->setId('history-russian-test');
    $page->setName('history-russian-test');
    $schema = Schema::make($page)->record($payment)->components([$entry]);

    $html = $schema->toHtml();
    $refund = $entry->getAction('refund')->arguments(['transaction' => $payment->id]);

    expect($html)
        ->toContain('Получено', 'Возвращено', 'Подробнее о транзакции', 'Учесть возврат')
        ->not->toContain('Received', 'Refunded', 'View transaction details', 'Record refund');
    expect($refund->getModalHeading())->toBe('Учесть возврат');
    expect($refund->getModalDescription())->toBe('Учитывайте операцию только после её выполнения вне этой системы.');
});
