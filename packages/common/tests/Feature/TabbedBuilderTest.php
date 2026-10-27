<?php

use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use PHPinnacle\Common\Enums\TabPosition;
use PHPinnacle\Common\Forms\TabbedBuilder;
use PHPinnacle\Common\Forms\TabbedBuilder\Block;

class CommonTabbedBuilderHost extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    /** @var array<string, mixed> */
    public array $data = [];
}

class CommonTabbedBuilderTestCase extends \Tests\TestCase
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

/** @param array<string, mixed> $arguments */
function call_tabbed_builder_action(TabbedBuilder $field, string $name, array $arguments): void
{
    $action = $field->getAction($name)($arguments);
    $action->callBefore();
    $action->call();
}

uses(CommonTabbedBuilderTestCase::class);

beforeEach(function () {
    $host = new CommonTabbedBuilderHost;
    $host->setId('common-tabbed-builder');
    $host->setName('common-tabbed-builder');
    $this->field = TabbedBuilder::make('content')->blocks([
        Block::make('text')
            ->label(fn (?array $state) => $state !== null && filled($state['title']) ? $state['title'] : 'Text')
            ->icon('heroicon-o-document-text')
            ->schema([TextInput::make('title')->required()->default('Untitled')]),
        Block::make('stop')->label('Stop'),
    ]);
    $this->schema = Schema::make($host)->statePath('data')->components([$this->field]);
    $this->schema->fill();
    view()->share('errors', new ViewErrorBag);
});

it('renders both orientations with independent block definitions and accessible tabs', function () {
    $this->schema->fill(['content' => [['type' => 'text', 'data' => ['title' => 'Welcome']]]]);
    expect($this->field->toHtml())
        ->toContain(
            'Welcome',
            'role="tab"',
            'role="tabpanel"',
            'aria-orientation="horizontal"',
            'fi-tabs-item',
            'x-sortable-handle',
        );
    expect($this->field->toHtml())->not->toContain('@js(', 'tabbed-builder-drag');
    expect($this->field->tabsPosition(TabPosition::Start)->toHtml())
        ->toContain('is-vertical', 'aria-orientation="vertical"');
    expect($this->field->toHtml())->not->toContain('tabbed-builder-drag');
    expect($this->field->getBlocks()[0])
        ->toBeInstanceOf(Block::class)
        ->not->toBeInstanceOf(\PHPinnacle\Common\Forms\InlineBuilder\Block::class);
});

it('defaults to top tabs and renders each configured side with its orientation', function (
    TabPosition $position,
    bool $vertical,
) {
    expect($this->field->getTabsPosition())->toBe(TabPosition::Top);
    $this->schema->fill(['content' => [['type' => 'text', 'data' => ['title' => 'Welcome']]]]);
    $html = $this->field->tabsPosition($position)->toHtml();
    expect($this->field->getTabsPosition())->toBe($position);
    expect($html)->toContain(
        'tabs-position-' . $position->value,
        'aria-orientation="' . ($vertical ? 'vertical' : 'horizontal') . '"',
        'data-vertical="' . ($vertical ? 'true' : 'false') . '"',
        'Welcome',
    );
})->with([
    'top' => [TabPosition::Top, false],
    'bottom' => [TabPosition::Bottom, false],
    'start' => [TabPosition::Start, true],
    'end' => [TabPosition::End, true],
]);

it('evaluates the tab position callback again when its configuration changes', function () {
    $position = TabPosition::Bottom;
    $this->field->tabsPosition(function () use (&$position) {
        return $position;
    });
    expect($this->field->toHtml())->toContain('tabs-position-bottom', 'aria-orientation="horizontal"');
    $position = TabPosition::End;
    expect($this->field->toHtml())->toContain('tabs-position-end', 'aria-orientation="vertical"');
});

it('adds repeated blocks with defaults and removes and reorders instances without losing data', function () {
    call_tabbed_builder_action($this->field, 'add', ['block' => 'text']);
    call_tabbed_builder_action($this->field, 'add', ['block' => 'text']);
    $keys = array_keys($this->field->getItems());
    $this->field->getChildSchema($keys[0])->getComponent('title')->state('First');
    $this->field->getChildSchema($keys[1])->getComponent('title')->state('Second');
    call_tabbed_builder_action($this->field, 'reorder', ['items' => array_reverse($keys)]);
    expect($this->schema->getState()['content'])->toBe([
        ['type' => 'text', 'data' => ['title' => 'Second']],
        ['type' => 'text', 'data' => ['title' => 'First']],
    ]);
    call_tabbed_builder_action($this->field, 'delete', ['item' => $keys[0]]);
    expect($this->schema->getState()['content'])->toBe([['type' => 'text', 'data' => ['title' => 'Second']]]);
});

it('allows the native delete action callback to customize the removal icon', function () {
    expect($this->field->getDeleteAction()->getIcon())->toBe('heroicon-m-x-mark');
    $configuredComponent = null;
    $this->field->deleteAction(function (Action $action, TabbedBuilder $component) use (&$configuredComponent) {
        $configuredComponent = $component;

        return $action->icon(Heroicon::Trash);
    });
    $clone = clone $this->field;
    expect($clone->getDeleteAction()->getIcon())->toBe(Heroicon::Trash);
    expect($configuredComponent)->toBe($clone);
});

it('renders the native empty state only while the builder has no blocks', function () {
    expect($this->field->getEmptyStateIcon())->toBeNull();
    expect($this->field->getEmptyStateDescription())->toBeNull();
    expect($this->field->toHtml())->toContain('fi-empty-state', 'Add a block to get started.', 'role="status"');
    call_tabbed_builder_action($this->field, 'add', ['block' => 'text']);
    expect($this->field->toHtml())->not->toContain('fi-empty-state');
    call_tabbed_builder_action($this->field, 'delete', ['item' => array_key_first($this->field->getItems())]);
    expect($this->field->toHtml())->toContain('fi-empty-state', 'Add a block to get started.');
});

it('renders custom empty state text and icons through Filament components', function () {
    $this->field
        ->emptyStateIcon(Heroicon::OutlinedDocumentText)
        ->emptyStateHeading('No content yet')
        ->emptyStateDescription('Choose a block to start.');
    expect($this->field->getEmptyStateIcon())->toBe(Heroicon::OutlinedDocumentText);
    expect($this->field->toHtml())
        ->toContain(
            'fi-empty-state-icon-bg',
            'No content yet',
            'Choose a block to start.',
        );
});

it('evaluates empty state callbacks with field injections and preserves explicit HTML', function () {
    $this->field
        ->label('Content')
        ->emptyStateIcon(fn () => 'heroicon-o-document-text')
        ->emptyStateHeading(
            fn (TabbedBuilder $component) => new HtmlString('<strong>No ' . $component->getLabel() . '</strong>'),
        )
        ->emptyStateDescription(fn () => new HtmlString('<em>Choose a block.</em>'));
    expect($this->field->getEmptyStateIcon())->toBe('heroicon-o-document-text');
    expect($this->field->toHtml())
        ->toContain('fi-empty-state-icon-bg', '<strong>No Content</strong>', '<em>Choose a block.</em>');
    $this->field->label('Blocks');
    expect($this->field->toHtml())->toContain('<strong>No Blocks</strong>');
});

it('hydrates formats validates and dehydrates every block through native schemas', function () {
    $this->field->blocks([
        Block::make('typed')
            ->columns(2)
            ->schema([
                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->default(1000)
                    ->formatStateUsing(fn ($state) => $state === null ? null : $state / 100)
                    ->dehydrateStateUsing(fn ($state) => (int) round($state * 100)),
                Toggle::make('enabled')->default(true),
                Repeater::make('rows')->schema([TextInput::make('value')->required()]),
                Hidden::make('temporary')->dehydrated(false),
            ]),
    ]);
    $this->schema->fill([
        'content' => [
            [
                'type' => 'typed',
                'data' => ['amount' => 1234, 'enabled' => true, 'rows' => [['value' => 'One']], 'temporary' => 'Draft'],
            ],
            ['type' => 'typed', 'data' => ['amount' => 5678, 'enabled' => false, 'rows' => []]],
        ],
    ]);
    $items = array_values($this->field->getItems());
    $first = $items[0]->getComponent('amount');
    $second = $items[1]->getComponent('amount');
    expect($first)->not->toBe($second);
    expect($first->getState())->toBe(12.34);
    expect($second->getState())->toBe(56.78);
    expect($this->schema->getState()['content'])->toBe([
        ['type' => 'typed', 'data' => ['amount' => 1234, 'enabled' => true, 'rows' => [['value' => 'One']]]],
        ['type' => 'typed', 'data' => ['amount' => 5678, 'enabled' => false, 'rows' => []]],
    ]);
    call_tabbed_builder_action($this->field, 'add', ['block' => 'typed']);
    $last = array_values($this->field->getItems())[2];
    expect($last->getComponent('amount')->getState())->toBe(10.0);
    expect($last->getComponent('enabled')->getState())->toBeTrue();
    $second->state('invalid');
    expect($this->schema->getState(...))->toThrow(ValidationException::class);
});

it('marks errors in inactive blocks without concealing them from schema validation', function () {
    $this->schema->fill([
        'content' => [
            ['type' => 'text', 'data' => ['title' => 'Valid']],
            ['type' => 'text', 'data' => ['title' => '']],
        ],
    ]);
    try {
        $this->schema->getState();
        $this->fail('The second block must fail validation.');
    } catch (ValidationException $exception) {
        $keys = array_keys($this->field->getItems());
        expect($exception->errors())->toHaveKey('data.content.' . $keys[1] . '.data.title');
        view()->share('errors', new ViewErrorBag()->put('default', $exception->validator->errors()));
        expect($this->field->toHtml())->toContain('tabbed-builder-error', 'x-on:expand.stop');
    }
});

it('rechecks block availability and item limits before mutations', function () {
    $allowed = true;
    $this->field
        ->blocks([Block::make('stop')->addable(function () use (&$allowed) {
            return $allowed;
        })])
        ->minItems(1)
        ->maxItems(2);
    call_tabbed_builder_action($this->field, 'add', ['block' => 'stop']);
    $allowed = false;
    expect($this->field->getBlockPickerBlocks())->toBe([]);
    expect(fn () => call_tabbed_builder_action($this->field, 'add', [
        'block' => 'stop',
    ]))->toThrow(ValidationException::class);
    $key = array_key_first($this->field->getItems());
    expect(fn () => call_tabbed_builder_action($this->field, 'delete', [
        'item' => $key,
    ]))->toThrow(ValidationException::class);
    $allowed = true;
    call_tabbed_builder_action($this->field, 'add', ['block' => 'stop']);
    expect(fn () => call_tabbed_builder_action($this->field, 'add', [
        'block' => 'stop',
    ]))->toThrow(ValidationException::class);
    expect($this->schema->getState()['content'])->toHaveCount(2);
    $this->field->disabled();
    expect(fn () => call_tabbed_builder_action($this->field, 'delete', [
        'item' => $key,
    ]))->toThrow(ValidationException::class);
    expect(fn () => call_tabbed_builder_action($this->field, 'reorder', [
        'items' => array_keys($this->field->getItems()),
    ]))->toThrow(ValidationException::class);
});

it('rejects incomplete duplicated or unknown reorder keys', function (array $order) {
    $this->schema->fill(['content' => [['type' => 'stop', 'data' => []], ['type' => 'stop', 'data' => []]]]);
    $original = $this->field->getRawState();
    $keys = array_keys($original);
    $items = array_map(fn ($index) => $keys[$index] ?? 'unknown', $order);
    expect(fn () => call_tabbed_builder_action($this->field, 'reorder', [
        'items' => $items,
    ]))->toThrow(ValidationException::class);
    expect($this->field->getRawState())->toBe($original);
})->with([
    'missing' => [[0]],
    'duplicate' => [[0, 0]],
    'unknown' => [[0, 2]],
]);

it('validates item types and total counts at submission', function () {
    $this->field->minItems(1);
    expect($this->schema->getState(...))->toThrow(ValidationException::class);
    $this->schema->fill(['content' => [['type' => 'unknown', 'data' => []]]]);
    expect($this->schema->getState(...))->toThrow(ValidationException::class);
});
