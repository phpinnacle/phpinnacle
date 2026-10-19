<?php

use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Panel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Request as RequestFacade;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use PHPinnacle\Recens\Livewire\Entries;
use PHPinnacle\Recens\Models\Recent;
use PHPinnacle\Recens\RecensPlugin;
use PHPinnacle\Recens\Recorder;
use Tests\TestCase;

uses(TestCase::class);

class RecentVisitor extends User
{
    public $timestamps = false;

    protected $table = 'users';

    protected $guarded = [];
}

class RecordedPage extends Page
{
    protected static ?string $title = 'Orders';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document';
}

beforeEach(function () {
    config()->set('phpinnacle-recens.user.model', RecentVisitor::class);
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
    });
    $migration = require __DIR__ . '/../../database/migrations/create_recens_tables.php';
    $migration->up();

    Filament::setCurrentPanel(Panel::make()->id('admin')->plugin(RecensPlugin::make()));
    $this->visitor = RecentVisitor::create(['name' => 'Ada']);
    $this->actingAs($this->visitor);
    RequestFacade::swap(Request::create('https://example.test/orders?sort=desc'));
});

afterEach(function () {
    Filament::setTenant(null, isQuiet: true);
    Filament::setCurrentPanel(null);
});

it('keeps independent histories when two users visit the same canonical URL', function () {
    $firstVisit = now()->startOfSecond();
    $this->travelTo($firstVisit);
    Recent::record(['title' => 'Orders', 'icon' => 'heroicon-o-document']);
    $firstId = Recent::list()[0]->id;

    $second = RecentVisitor::create(['name' => 'Grace']);
    $this->actingAs($second);
    Recent::record(['title' => 'Other orders', 'icon' => 'heroicon-o-document']);
    $secondId = Recent::list()[0]->id;

    $this->actingAs($this->visitor);
    $this->travelTo($firstVisit->copy()->addMinute());
    RequestFacade::swap(Request::create('https://example.test/orders?sort=asc'));
    Recent::record(['title' => 'Updated orders', 'icon' => 'heroicon-o-document']);

    expect(Recent::query()->count())
        ->toBe(2)
        ->and(Recent::list()[0]->id)
        ->toBe($firstId)
        ->and(Recent::list()[0]->url)
        ->toBe('https://example.test/orders')
        ->and(Recent::list()[0]->title)
        ->toBe('Updated orders')
        ->and(Recent::query()->findOrFail($secondId)->user_id)
        ->toEqual($second->id)
        ->and((string) Recent::query()->findOrFail($secondId)->created_at)
        ->toBe($firstVisit->toDateTimeString());
});

it('uses each panels registrations after switching panels and starting another request', function () {
    $first = Panel::make()->id('first')->plugin(RecensPlugin::make()->scopes([
        RecordedPage::class => fn () => ['title' => 'First panel'],
    ]));
    $second = Panel::make()->id('second')->plugin(RecensPlugin::make()->scopes([
        RecordedPage::class => fn () => ['title' => 'Second panel'],
    ]));

    foreach ([
        [$first,  'First panel'],
        [$second, 'Second panel'],
        [$first,  'First panel'],
    ] as [$panel, $title]) {
        app()->forgetScopedInstances();
        Filament::clearResolvedInstance('filament');
        Filament::setCurrentPanel($panel);
        RequestFacade::swap(Request::create('https://example.test/' . $panel->getId()));
        app(Recorder::class)->record(new RecordedPage);

        expect(Recent::list()[0]->title)->toBe($title);
    }
});

class RecentTenant extends Model
{
    public $timestamps = false;

    protected $table = 'tenants';

    protected $guarded = [];
}

it('keeps pins in stable order above limited history and excludes them from pruning', function () {
    $start = now()->startOfSecond()->subDays(10);
    $this->travelTo($start);

    foreach (['first', 'second', 'third', 'fourth'] as $index => $path) {
        $this->travelTo($start->copy()->addMinutes($index));
        RequestFacade::swap(Request::create('https://example.test/' . $path));
        Recent::record(['title' => $path, 'icon' => 'heroicon-o-document']);
    }

    $first = Recent::query()->where('title', 'first')->sole();
    $second = Recent::query()->where('title', 'second')->sole();
    $first->pin();
    $this->travelTo($start->copy()->addMinutes(5));
    $second->pin();
    $this->travelTo($start->copy()->addMinutes(6));
    $first->pin();
    RequestFacade::swap(Request::create($first->url));
    Recent::record(['title' => 'first', 'icon' => 'heroicon-o-document']);

    expect(array_column(Recent::list(1), 'title'))->toBe(['first', 'second', 'fourth']);

    $this->travelTo($start->copy()->addDays(10));
    expect(new Recent()->prunable()->count())->toBe(2);
    new Recent()->prunable()->delete();

    expect(array_column(Recent::list(1), 'title'))->toBe(['first', 'second']);
    $first->unpin();
    expect(
        new Recent()
            ->prunable()
            ->pluck('id')
            ->all(),
    )->toBe([$first->id]);
    config()->set('phpinnacle-recens.prune.enabled', false);
    expect(new Recent()->prunable()->count())->toBe(0);
});

it('pins unpins removes and clears history through the menu while preserving pins', function () {
    $plugin = RecensPlugin::get()->limit(1);
    Recent::record(['title' => 'Orders', 'icon' => 'heroicon-o-document']);
    $orders = Recent::list()[0];

    $component = Livewire::test(Entries::class)
        ->assertSee('Orders')
        ->assertSee('Pin Orders')
        ->assertSee('Remove Orders from history')
        ->call('togglePin', $orders->id)
        ->assertSee('Pinned')
        ->assertSee('Unpin Orders');
    expect($orders->refresh()->pinned_at)->not->toBeNull();
    $component->call('togglePin', $orders->id)->assertSee('Pin Orders');
    expect($orders->refresh()->pinned_at)->toBeNull();
    $component->call('togglePin', $orders->id);

    foreach (['Customers', 'Invoices'] as $title) {
        RequestFacade::swap(Request::create('https://example.test/' . $title));
        Recent::record(['title' => $title, 'icon' => 'heroicon-o-document']);
    }

    $component->call('clear')->assertSee('Orders')->assertDontSee('Customers')->assertDontSee('Invoices');
    expect(Recent::query()->count())->toBe(1);
    $component->call('remove', $orders->id)->assertDontSee('Orders');
    expect(Recent::query()->count())->toBe(0)->and($plugin->getLimit())->toBe(1);
});

it('isolates menu reads and writes by user panel and tenant', function (string $boundary) {
    Schema::create('tenants', function (Blueprint $table) {
        $table->id();
    });
    Schema::table('recent', function (Blueprint $table) {
        $table->unsignedBigInteger('tenant_id')->nullable();
    });
    config()->set('phpinnacle-recens.tenancy.model', RecentTenant::class);
    $firstTenant = RecentTenant::create();
    $secondTenant = RecentTenant::create();
    Filament::setTenant($firstTenant, isQuiet: true);
    $firstPanel = Filament::getCurrentPanel();

    Recent::record(['title' => 'Private orders', 'icon' => 'heroicon-o-document']);
    $private = Recent::list()[0];

    match ($boundary) {
        'user' => $this->actingAs(RecentVisitor::create(['name' => 'Grace'])),
        'panel' => Filament::setCurrentPanel(Panel::make()->id('member')->plugin(RecensPlugin::make())),
        'tenant' => Filament::setTenant($secondTenant, isQuiet: true),
    };

    expect(Recent::list())->toBe([]);
    Recent::record(['title' => 'My orders', 'icon' => 'heroicon-o-document']);
    $own = Recent::list()[0];
    expect($own->id)->not->toBe($private->id)->and(Recent::query()->count())->toBe(2);

    $component = Livewire::test(Entries::class)->assertSee('My orders')->assertDontSee('Private orders');
    $component->call('togglePin', $private->id)->assertNotFound();
    Livewire::test(Entries::class)->call('remove', $private->id)->assertNotFound();
    Livewire::test(Entries::class)->call('clear');

    expect(Recent::query()->count())->toBe(1)->and($private->refresh()->pinned_at)->toBeNull();
    $this->actingAs($this->visitor);
    Filament::setCurrentPanel($firstPanel);
    Filament::setTenant($firstTenant, isQuiet: true);
    expect(Recent::list()[0]->id)->toBe($private->id);
})->with(['user', 'panel', 'tenant']);

it('does not expose or modify history for guests or a different panel guard', function () {
    Recent::record(['title' => 'Private orders', 'icon' => 'heroicon-o-document']);
    $entry = Recent::list()[0];
    config()->set('auth.guards.members', ['driver' => 'session', 'provider' => 'users']);
    Filament::setCurrentPanel(Panel::make()->id('member')->authGuard('members')->plugin(RecensPlugin::make()));

    Recent::record(['title' => 'Guest', 'icon' => 'heroicon-o-document']);
    expect(Recent::list())->toBe([])->and(Recent::query()->count())->toBe(1);
    Livewire::test(Entries::class)->assertDontSee('Private orders')->call('togglePin', $entry->id)->assertForbidden();
    Livewire::test(Entries::class)->call('remove', $entry->id)->assertForbidden();
    Livewire::test(Entries::class)->call('clear')->assertForbidden();
});

it('does not record Livewire updates', function () {
    request()->headers->set('X-Livewire', 'true');
    app(Recorder::class)->record(new RecordedPage);
    expect(Recent::query()->count())->toBe(0);
});

it('validates entry identifiers at the menu boundary', function (string $action) {
    Recent::record(['title' => 'Orders', 'icon' => 'heroicon-o-document']);
    Livewire::test(Entries::class)->call($action, 'not-a-uuid')->assertHasErrors(['id' => 'uuid']);
    expect(Recent::query()->count())->toBe(1)->and(Recent::list()[0]->pinned_at)->toBeNull();
})->with(['togglePin', 'remove']);
