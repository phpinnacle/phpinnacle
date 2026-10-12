<?php

use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use PHPinnacle\Common\Enums\ItemPickerMode;
use PHPinnacle\Common\Forms\Item;
use PHPinnacle\Common\Forms\ItemBadge;
use PHPinnacle\Common\Forms\ItemGroup;
use PHPinnacle\Common\Forms\ItemPicker;
use Tests\TestCase;

uses(TestCase::class);

it('uses title case labels from factory values and retains the original selection identity', function () {
    $item = Item::make('dry food');
    $group = ItemGroup::make('pet products')->children([$item]);
    $picker = ItemPicker::make('choice')->items([$group]);
    [$schema] = common_picker_form($picker);
    $schema->fill(['choice' => 'dry food']);
    view()->share('errors', new ViewErrorBag);

    expect($picker->toHtml())->toContain('Pet Products', 'Dry Food');
    expect($schema->getState())->toBe(['choice' => 'dry food']);
    expect($item->getLabel())->toBe('Dry Food');
    expect($group->getLabel())->toBe('Pet Products');
});

it('renders fluent item and group metadata and applies a disabled condition', function () {
    $item = Item::make('leaf')
        ->label('Custom leaf')
        ->description('Leaf details')
        ->icon('heroicon-o-cube')
        ->color('success')
        ->disabled();
    $parent = Item::make('parent')->label('Custom parent')->children([$item]);
    $group = ItemGroup::make('group')->label('Custom group')->description('Group details')->children([$parent]);
    $picker = ItemPicker::make('choice')->items([$group]);
    [$schema] = common_picker_form($picker);
    $schema->fill(['choice' => 'leaf']);
    view()->share('errors', new ViewErrorBag);

    expect($picker->toHtml())
        ->toContain(
            'Custom leaf',
            'Leaf details',
            'Custom parent',
            'Custom group',
            'Group details',
            '--success-600',
            'aria-level="2"',
        );
    expect($schema->getState(...))->toThrow(ValidationException::class);
    expect($item->getDescription())->toBe('Leaf details');
    expect($item->getIcon())->toBe('heroicon-o-cube');
    expect($item->getColor())->toBe('success');
    expect($group->getDescription())->toBe('Group details');

    $item->disabled(false);
    expect($schema->getState())->toBe(['choice' => 'leaf']);
});

it('renders groups nested metadata and independent compact and dense settings in both modes', function (
    ItemPickerMode $mode,
    bool $compact,
    bool $dense,
) {
    $picker = ItemPicker::make('choice')
        ->items([
            new ItemGroup('Catalog', [
                new Item('parent', 'Products', 'Description <script>alert(1)</script>', 'heroicon-o-cube', 'success', [
                    new Item('child', 'Food', children: [new Item('leaf', 'Dry food')]),
                ]),
            ]),
        ])
        ->mode($mode)
        ->compact($compact)
        ->dense($dense);
    [$schema] = common_picker_form($picker);
    $schema->fill(['choice' => 'leaf']);
    view()->share('errors', new ViewErrorBag);

    $html = $picker->toHtml();

    expect($html)
        ->toContain(
            'Catalog',
            'Products',
            'Dry food',
            'aria-level="3"',
            'role="treeitem"',
            '--success-600',
        )
        ->toContain('&lt;script&gt;alert(1)&lt;/script&gt;')
        ->not->toContain('<script>alert(1)</script>', '<select');
    expect(str_contains($html, 'common-item-picker--compact'))->toBe($compact);
    expect(str_contains($html, 'common-item-picker--dense'))->toBe($dense);
    expect(str_contains($html, 'popover="auto"'))->toBe($mode === ItemPickerMode::Select);
    expect($schema->getState())->toBe(['choice' => 'leaf']);
})
    ->with(ItemPickerMode::cases())
    ->with(['stacked descriptions' => false, 'inline descriptions' => true])
    ->with(['regular spacing' => false, 'dense spacing' => true]);

it('uses images instead of configured icons in tree rows and selected values', function (
    ItemPickerMode $mode,
    bool $multiple,
) {
    $item = Item::make('user')->label('Anna')->image('/avatars/anna.jpg')->icon('heroicon-o-user');
    $picker = ItemPicker::make('choice')->mode($mode)->multiple($multiple)->compact()->items([$item]);
    [$schema] = common_picker_form($picker);
    $state = $multiple ? ['user'] : 'user';
    $schema->fill(['choice' => $state]);
    view()->share('errors', new ViewErrorBag);
    $document = new DOMDocument;
    $document->loadHTML($picker->toHtml(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);
    $images = $xpath->query('//img');

    expect($images->length)->toBe($mode === ItemPickerMode::Select ? 2 : 1);
    foreach ($images as $image) {
        expect($image->getAttribute('src'))->toBe('/avatars/anna.jpg');
        expect($image->getAttribute('alt'))->toBe('');
    }
    expect($xpath->query('//*[contains(@class, "common-item-picker__icon")]//svg')->length)->toBe(0);
    expect($item->getImage())->toBe('/avatars/anna.jpg');
    expect($item->getIcon())->toBe('heroicon-o-user');
    expect($schema->getState())->toBe(['choice' => $state]);
})
    ->with(ItemPickerMode::cases())
    ->with(['single' => false, 'multiple' => true]);

it('renders circular image and icon containers in both modes and multiple selected values', function (
    ItemPickerMode $mode,
) {
    $picker = ItemPicker::make('choice')
        ->mode($mode)
        ->circular()
        ->multiple()
        ->items([
            Item::make('user')->image('/avatars/anna.jpg')->icon('heroicon-o-user'),
            Item::make('category')->icon('heroicon-o-cube'),
        ]);
    [$schema] = common_picker_form($picker);
    $schema->fill(['choice' => ['user', 'category']]);
    view()->share('errors', new ViewErrorBag);
    $html = $picker->toHtml();
    $document = new DOMDocument;
    $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);
    $circularPicker = $xpath->query('//*[contains(@class, "common-item-picker--circular")]')->item(0);
    $copies = $mode === ItemPickerMode::Select ? 2 : 1;

    expect($circularPicker)->not->toBeNull();
    expect($xpath->query('.//img[contains(@class, "fi-circular")]', $circularPicker)->length)->toBe($copies);
    expect($xpath->query('.//*[contains(@class, "common-item-picker__icon")]//svg', $circularPicker)->length)
        ->toBe($copies);
    expect($schema->getState())->toBe(['choice' => ['user', 'category']]);
})->with(ItemPickerMode::cases());

it('keeps the default shape and reevaluates a circular condition', function () {
    $picker = ItemPicker::make('choice')->items([Item::make('user')->image('/avatars/anna.jpg')]);
    [$schema] = common_picker_form($picker);
    $schema->fill(['choice' => 'user']);
    view()->share('errors', new ViewErrorBag);
    expect($picker->toHtml())->not->toContain('common-item-picker--circular', 'fi-circular');
    $circular = true;
    $picker->circular(function () use (&$circular) {
        return $circular;
    });

    expect($picker->toHtml())->toContain('common-item-picker--circular', 'fi-circular');

    $circular = false;
    expect($picker->toHtml())->not->toContain('common-item-picker--circular', 'fi-circular');
});

it('restores the configured icon when the image is cleared', function () {
    $item = new Item('user', icon: 'heroicon-o-user', image: '/avatars/anna.jpg');
    $picker = ItemPicker::make('choice')->items([$item]);
    [$schema] = common_picker_form($picker);
    $schema->fill(['choice' => 'user']);
    view()->share('errors', new ViewErrorBag);
    expect($picker->toHtml())->toContain('<img');

    $item->image(null);
    $html = $picker->toHtml();

    expect($html)->not->toContain('<img');
    expect($html)->toContain('<svg');
    expect($item->getImage())->toBeNull();
});

it('escapes image URLs without turning them into HTML attributes', function () {
    $url = '/avatar.jpg" onerror="alert(1)';
    $picker = ItemPicker::make('choice')->items([Item::make('user')->image($url)]);
    [$schema] = common_picker_form($picker);
    $schema->fill();
    view()->share('errors', new ViewErrorBag);
    $document = new DOMDocument;
    $document->loadHTML($picker->toHtml(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $image = $document->getElementsByTagName('img')->item(0);

    expect($image->getAttribute('src'))->toBe($url);
    expect($image->hasAttribute('onerror'))->toBeFalse();
});

it('renders accessible item and group badges without adding them to the select trigger', function (
    ItemPickerMode $mode,
    bool $multiple,
) {
    $badge = ItemBadge::make('New')->label('Verified')->color('success')->icon('heroicon-m-check-badge');
    $picker = ItemPicker::make('choice')
        ->mode($mode)
        ->multiple($multiple)
        ->items([
            ItemGroup::make('catalog')
                ->badges([ItemBadge::make(0)])
                ->children([
                    Item::make('leaf')
                        ->description('Leaf description')
                        ->badges([
                            $badge,
                            ItemBadge::make('<script>alert(1)</script>'),
                        ]),
                ]),
        ]);
    [$schema] = common_picker_form($picker);
    $state = $multiple ? ['leaf'] : 'leaf';
    $schema->fill(['choice' => $state]);
    view()->share('errors', new ViewErrorBag);
    $html = $picker->toHtml();
    $document = new DOMDocument;
    $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);
    $badges = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " fi-badge ")]');
    $item = $xpath->query('//*[@role="treeitem"]')->item(0);

    expect($badges->length)->toBe(3);
    expect(trim($badges->item(0)->textContent))->toBe('0');
    expect($badges->item(1)->getAttribute('class'))->toContain('fi-color-success');
    expect($badges->item(1)->getElementsByTagName('svg')->length)->toBe(1);
    expect($html)->toContain('&lt;script&gt;alert(1)&lt;/script&gt;');
    expect($html)->not->toContain('<script>alert(1)</script>');
    foreach (explode(' ', $item->getAttribute('aria-describedby')) as $id) {
        expect($document->getElementById($id))->not->toBeNull();
    }
    expect($schema->getState())->toBe(['choice' => $state]);
    expect($badge->getLabel())->toBe('Verified');
    expect($badge->getIcon())->toBe('heroicon-m-check-badge');
    expect($badge->getColor())->toBe('success');
    if ($mode === ItemPickerMode::Select) {
        expect($document->getElementById($picker->getId())->textContent)
            ->toContain('Leaf')
            ->not->toContain('Verified', '<script>alert(1)</script>');
    }
})
    ->with(ItemPickerMode::cases())
    ->with(['single' => false, 'multiple' => true]);

it('keeps embedded groups out of tree depth and keyboard parent relationships', function () {
    $picker = ItemPicker::make('choice')
        ->branches(false)
        ->items([
            ItemGroup::make('catalog')->children([
                Item::make('root')->children([
                    ItemGroup::make('category')->children([Item::make('leaf')]),
                ]),
            ]),
        ]);
    [$schema] = common_picker_form($picker);
    $schema->fill(['choice' => 'leaf']);
    view()->share('errors', new ViewErrorBag);

    $html = $picker->toHtml();
    $nodes = $picker->getViewData()['nodes'];

    expect($html)->toContain('aria-level="2"');
    expect($nodes[2]['group'])->toBeTrue();
    expect($nodes[3])->toBe([
        'key' => '-0-0-0-0',
        'value' => 'leaf',
        'group' => false,
        'disabled' => false,
        'selectable' => true,
        'branch' => false,
        'ancestors' => ['-0-0'],
        'parent' => '-0-0',
    ]);
    expect($nodes[1]['selectable'])->toBeFalse();
    expect($schema->getState())->toBe(['choice' => 'leaf']);
});

it('rejects unavailable disabled and malformed values at the form boundary', function (mixed $value) {
    [$schema] = common_picker_form(ItemPicker::make('choice')
        ->required()
        ->items([
            new ItemGroup('Group', [new Item('allowed', 'Allowed'), new Item('disabled', 'Disabled', disabled: true)]),
        ]));
    $schema->fill(['choice' => $value]);

    expect($schema->getState(...))->toThrow(ValidationException::class);
})->with([
    'unavailable' => 'missing',
    'group label' => 'Group',
    'disabled' => 'disabled',
    'array' => [['allowed']],
    'required' => null,
]);

it('rejects every parent level and accepts grouped leaves when branches are disabled', function (string $parent) {
    $picker = ItemPicker::make('choice')
        ->label('Choice')
        ->branches(false)
        ->items([
            new ItemGroup('Group', [
                new Item('root', 'Root', children: [
                    new Item('child', 'Child', children: [new Item('leaf', 'Leaf')]),
                ]),
                new Item('grouped', 'Grouped parent', children: [new ItemGroup('Nested group', [new Item(
                    'nested-leaf',
                    'Nested leaf',
                )])]),
            ]),
        ]);
    [$schema] = common_picker_form($picker);
    $schema->fill(['choice' => $parent]);

    try {
        $schema->getState();
        test()->fail('A parent must not be selectable when branches are disabled.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toBe(['data.choice' => ['The selected choice is invalid.']]);
    }

    $schema->fill(['choice' => 'leaf']);
    expect($schema->getState())->toBe(['choice' => 'leaf']);
    $schema->fill(['choice' => 'nested-leaf']);
    expect($schema->getState())->toBe(['choice' => 'nested-leaf']);
})->with(['root', 'child', 'grouped']);

it('allows branches by default and reevaluates a dynamic branches condition', function () {
    $picker = ItemPicker::make('choice')->items([new Item('parent', 'Parent', children: [new Item('leaf', 'Leaf')])]);
    [$schema] = common_picker_form($picker);
    $schema->fill(['choice' => 'parent']);
    expect($schema->getState())->toBe(['choice' => 'parent']);
    $allowBranches = false;
    $picker->branches(function () use (&$allowBranches) {
        return $allowBranches;
    });

    expect($schema->getState(...))->toThrow(ValidationException::class);

    $allowBranches = true;
    expect($schema->getState())->toBe(['choice' => 'parent']);
});

it('supports optional empty selection and reevaluates dynamic items for validation', function () {
    $items = [new Item('first', 'First')];
    $picker = ItemPicker::make('choice')->items(function () use (&$items) {
        return $items;
    });
    [$schema] = common_picker_form($picker);
    $schema->fill(['choice' => null]);
    expect($schema->getState())->toBe(['choice' => null]);
    $schema->fill(['choice' => 'first']);
    expect($schema->getState())->toBe(['choice' => 'first']);

    $items = [new Item('second', 'Second')];

    expect($schema->getState(...))->toThrow(ValidationException::class);
});

it('renders an empty picker and preserves a saved disabled selection', function () {
    $picker = ItemPicker::make('choice')->items([]);
    [$schema] = common_picker_form($picker);
    $schema->fill();
    view()->share('errors', new ViewErrorBag);
    expect($picker->toHtml())->toContain('No items available');
    $picker
        ->items([new Item('selected', 'Saved')])
        ->disabled()
        ->saved();
    $schema->fill(['choice' => 'selected']);

    expect($schema->getState())->toBe(['choice' => 'selected']);
    expect($picker->toHtml())->toContain('aria-disabled="true"');
});

it('renders and saves multiple selections in both modes without trigger descriptions', function (
    ItemPickerMode $mode,
    bool $compact,
) {
    $picker = common_multiple_picker()->mode($mode)->compact($compact);
    [$schema] = common_picker_form($picker);
    $schema->fill(['choice' => ['leaf', 'other']]);
    view()->share('errors', new ViewErrorBag);

    $html = $picker->toHtml();

    expect($schema->getState())->toBe(['choice' => ['leaf', 'other']]);
    expect($html)->toContain('aria-multiselectable="true"', 'Leaf description', 'Other description');
    if ($mode === ItemPickerMode::Select) {
        $document = new DOMDocument;
        $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        expect($document->getElementById($picker->getId())->textContent)
            ->toContain('Leaf', 'Other')
            ->not->toContain('Leaf description', 'Other description');
    }
})
    ->with(ItemPickerMode::cases())
    ->with(['regular' => false, 'compact' => true]);

it('rejects invalid multiple values including duplicates and forbidden parents', function (mixed $state) {
    [$schema] = common_picker_form(common_multiple_picker()->branches(false)->required());
    $schema->fill(['choice' => $state]);

    expect($schema->getState(...))->toThrow(ValidationException::class);
})->with([
    'unknown' => [['leaf', 'missing']],
    'parent' => [['leaf', 'root']],
    'disabled' => [['disabled']],
    'duplicate' => [['leaf', 'leaf']],
    'nested' => [[['leaf']]],
    'integer' => [[0]],
    'scalar' => 'leaf',
    'associative' => [['key' => 'leaf']],
    'empty required' => [[]],
]);

it('uses an empty list for optional multiple selection and keeps numeric identities as strings', function () {
    [$schema] = common_picker_form(common_multiple_picker());
    $schema->fill();
    expect($schema->getState())->toBe(['choice' => []]);
    $schema->fill(['choice' => null]);
    expect($schema->getState())->toBe(['choice' => []]);

    $schema->fill(['choice' => ['0', 'root', 'leaf']]);

    expect($schema->getState())->toBe(['choice' => ['0', 'root', 'leaf']]);
});

function common_multiple_picker(): ItemPicker
{
    return ItemPicker::make('choice')
        ->multiple()
        ->items([
            new ItemGroup('Group', [
                new Item('root', 'Root', children: [new Item('leaf', 'Leaf', 'Leaf description')]),
                new Item('other', 'Other', 'Other description'),
                new Item('0', 'Zero'),
                new Item('disabled', 'Disabled', disabled: true),
            ]),
        ]);
}

/** @return array{Schema, Component} */
function common_picker_form(ItemPicker $picker): array
{
    $livewire = new class extends Component implements HasSchemas {
        use InteractsWithSchemas;

        /** @var array<string, mixed> */
        public array $data = [];
    };
    $livewire->setId('common-picker-test');
    $livewire->setName('common-picker-test');

    return [Schema::make($livewire)->statePath('data')->components([$picker]), $livewire];
}
