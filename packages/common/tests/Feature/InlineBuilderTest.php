<?php

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use PHPinnacle\Common\Forms\InlineBuilder;
use PHPinnacle\Common\Forms\InlineBuilder\Block;

class CommonInlineBuilderHost extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    /** @var array<string, mixed> */
    public array $data = [];
}

class CommonInlineBuilderTestCase extends \Tests\TestCase
{
    protected function getPackageProviders($app): array
    {
        return array_values(array_filter(
            parent::getPackageProviders($app),
            fn (string $provider) => (
                !str_starts_with($provider, 'PHPinnacle\\') || str_starts_with($provider, 'PHPinnacle\\Common\\')
            ),
        ));
    }
}

uses(CommonInlineBuilderTestCase::class);

beforeEach(function () {
    $this->field = InlineBuilder::make('steps')->blocks([
        Block::make('notify')
            ->label('Notify')
            ->color('success')
            ->schema([
                TextInput::make('message')->required(),
            ]),
        Block::make('stop')->label('Stop'),
    ]);
    $host = new CommonInlineBuilderHost;
    $host->setId('common-inline-builder');
    $host->setName('common-inline-builder');
    $this->schema = Schema::make($host)->statePath('data')->components([$this->field]);
    $this->schema->fill();
    $this->action = $this->field->getAction('inlineBuilder');
    view()->share('errors', new ViewErrorBag);
});

it('runs without registering other PHPinnacle packages and renders block metadata', function () {
    $providers = array_filter(array_keys(app()->getLoadedProviders()), fn (string $provider) => str_starts_with(
        $provider,
        'PHPinnacle\\',
    ));
    expect(array_values($providers))->toBe([\PHPinnacle\Common\CommonServiceProvider::class]);
    $this->field->state([['type' => 'notify', 'data' => ['message' => 'Hello']]]);
    expect($this->field->toHtml())
        ->toContain('Notify', 'Remove Notify', 'fi-color-success', 'inlineBuilder(', 'data-inline-builder-item');
});

it('adds repeated blocks and edits and removes only the selected instance', function () {
    ($this->action)(['block' => 'notify'])->data(['data' => ['message' => 'First']])->call();
    ($this->action)(['block' => 'notify'])->data(['data' => ['message' => 'Second']])->call();
    ($this->action)(['block' => 'notify', 'index' => 1])->data(['data' => ['message' => 'Edited']])->call();
    expect($this->field->getItems())->toBe([
        ['type' => 'notify', 'data' => ['message' => 'First']],
        ['type' => 'notify', 'data' => ['message' => 'Edited']],
    ]);
    ($this->action)(['block' => 'notify', 'index' => 0, 'remove' => true])->call();
    expect($this->schema->getState()['steps'])->toBe([['type' => 'notify', 'data' => ['message' => 'Edited']]]);
});

it('adds settings-free blocks immediately and keeps removal out of settings modals', function () {
    expect(($this->action)(['block' => 'stop'])->shouldOpenModal())->toBeFalse();
    ($this->action)(['block' => 'stop'])->call();
    expect($this->field->getItems())->toBe([['type' => 'stop', 'data' => []]]);
    expect(($this->action)(['block' => 'notify'])->getExtraModalFooterActions())->toBe([]);
});

it('defers schema callbacks until settings are opened and supplies the instance index', function () {
    $indices = [];
    $this->field->blocks([
        Block::make('custom')->schema(function (?int $index) use (&$indices) {
            $indices[] = $index;

            return [TextInput::make('message')->required()];
        }),
    ]);
    $action = \PHPinnacle\Common\Forms\InlineBuilder\BuilderAction::make($this->field)->schemaComponent($this->field);
    $action->toHtml();
    expect($indices)->toBe([]);
    expect($action(['block' => 'custom'])->shouldOpenModal())->toBeTrue();
    expect($indices)->toBe([null]);
    $this->field->state([['type' => 'custom', 'data' => ['message' => 'Hello']]]);
    $action(['block' => 'custom', 'index' => 0])->getSchema(Schema::make($this->field->getLivewire()));
    expect($indices)->toContain(0);
});

it('validates native fields and preserves typed nested data through dehydration', function () {
    $this->field->blocks([
        Block::make('typed')->schema([
            TextInput::make('count')
                ->integer()
                ->required()
                ->dehydrateStateUsing(fn ($state) => (int) $state),
            Toggle::make('enabled'),
            Repeater::make('rows')->schema([TextInput::make('value')->required()]),
            Hidden::make('temporary')->dehydrated(false),
        ]),
    ])->state([[
        'type' => 'typed',
        'data' => ['count' => '7', 'enabled' => true, 'rows' => [['value' => 'One']], 'temporary' => 'Draft'],
    ]]);
    expect($this->schema->getState()['steps'])->toBe([
        ['type' => 'typed', 'data' => ['count' => 7, 'enabled' => true, 'rows' => [['value' => 'One']]]],
    ]);
    $this->field->state([['type' => 'typed', 'data' => ['count' => 'invalid', 'rows' => []]]]);
    expect($this->schema->getState(...))->toThrow(ValidationException::class);
});

it('validates user-controlled item structure', function (mixed $state) {
    $this->field->state($state);
    expect($this->schema->getState(...))->toThrow(ValidationException::class);
})->with([
    'scalar' => ['invalid'],
    'missing type' => [[['data' => []]]],
    'unknown type' => [[['type' => 'unknown']]],
    'non-array data' => [[['type' => 'notify', 'data' => 'invalid']]],
    'unexpected property' => [[['type' => 'stop', 'extra' => true]]],
    'not a list' => [['first' => ['type' => 'stop']]],
]);

it('uses current availability for additions while preserving editing and removal', function () {
    $available = true;
    $this->field->blocks([Block::make('notify')
        ->schema([TextInput::make('message')])
        ->addable(function () use (&$available) {
            return $available;
        })]);
    $action = \PHPinnacle\Common\Forms\InlineBuilder\BuilderAction::make($this->field)->schemaComponent($this->field);
    $available = false;
    expect($this->field->getAvailableBlocks())->toBe([]);
    expect(fn () => $action(['block' => 'notify'])->call())->toThrow(ValidationException::class);
    $this->field->state([['type' => 'notify', 'data' => ['message' => 'Hello']]]);
    expect($action(['block' => 'notify', 'index' => 0])->isDisabled())->toBeFalse();
    $action(['block' => 'notify', 'index' => 0, 'remove' => true])->call();
    expect($this->field->getItems())->toBe([]);
});

it('rechecks the proposed full order against current application state', function () {
    $allowed = true;
    $this->field->reorderableUsing(function (array $items) use (&$allowed) {
        return $allowed || $items[0]['data']['message'] === 'First';
    })->state([
        ['type' => 'notify', 'data' => ['message' => 'First']],
        ['type' => 'notify', 'data' => ['message' => 'Second']],
    ]);
    expect($this->field->getAllowedMoves())->toBe([[0, 1], [0, 1]]);
    $allowed = false;
    $original = $this->field->getItems();
    expect(fn () => ($this->action)(['order' => ['1', '0']])->call())->toThrow(ValidationException::class);
    expect($this->field->getItems())->toBe($original);
    expect($this->field->getAllowedMoves())->toBe([[0], [1]]);
    $allowed = true;
    ($this->action)(['order' => ['1', '0']])->call();
    expect($this->field->getItems())->toBe([$original[1], $original[0]]);
});

it('rejects reorder requests that lose, duplicate or introduce items', function (array $order) {
    $this->field->state([['type' => 'stop'], ['type' => 'stop']]);
    $original = $this->field->getItems();
    expect(fn () => ($this->action)(['order' => $order])->call())->toThrow(ValidationException::class);
    expect($this->field->getItems())->toBe($original);
})->with([
    'missing' => [[0]],
    'duplicate' => [[1, 1]],
    'unknown' => [[0, 2]],
    'not a list' => [['first' => 1, 'second' => 0]],
]);

it('rejects editing or removing a different block instance', function (array $arguments) {
    $this->field->state([['type' => 'notify', 'data' => ['message' => 'First']]]);
    expect(fn () => ($this->action)($arguments)->data([])->call())->toThrow(ValidationException::class);
})->with([
    'missing index' => [['block' => 'notify', 'remove' => true]],
    'unknown instance' => [['block' => 'notify', 'index' => 1]],
    'different type' => [['block' => 'stop', 'index' => 0]],
]);

it('honors addable, deletable, reorderable and disabled controls', function () {
    $this->field
        ->state([['type' => 'stop']])
        ->addable(false)
        ->deletable(false)
        ->reorderable(false);
    expect(($this->action)(['block' => 'stop'])->isDisabled())->toBeTrue();
    expect(($this->action)(['block' => 'stop', 'index' => 0, 'remove' => true])->isDisabled())->toBeTrue();
    expect(($this->action)(['order' => [0]])->isDisabled())->toBeTrue();
    expect($this->field->toHtml())->not->toContain('x-sortable="', 'class="inline-builder-item-remove"');
    $this->field->addable()->deletable()->reorderable()->disabled();
    expect(($this->action)(['block' => 'stop'])->isDisabled())->toBeTrue();
    expect(($this->action)(['order' => [0]])->isDisabled())->toBeTrue();
    expect($this->field->getAllowedMoves())->toBe([[]]);
});
