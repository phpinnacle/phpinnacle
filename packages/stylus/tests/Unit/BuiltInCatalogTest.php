<?php

use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Group;
use PHPinnacle\Stylus\BuiltInCatalog;
use PHPinnacle\Stylus\Enums\ConditionKind;
use PHPinnacle\Stylus\Enums\FilterOutput;
use Tests\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

uses(TestCase::class);

it('provides the built-in Twig filters', function () {
    $filters = collect(BuiltInCatalog::filters())->keyBy->getName();

    expect($filters->keys()->all())
        ->toBe([
            'default',
            'title',
            'capitalize',
            'upper',
            'lower',
            'trim',
            'nl2br',
            'striptags',
            'format',
            'replace',
            'escape',
            'url_encode',
            'convert_encoding',
            'date',
            'date_modify',
            'abs',
            'round',
            'number_format',
            'sort',
            'first',
            'last',
            'keys',
            'slice',
            'reverse',
            'shuffle',
        ])
        ->and($filters->map->getColor()->all())
        ->toBe([
            'default' => 'info',
            'title' => 'info',
            'capitalize' => 'info',
            'upper' => 'info',
            'lower' => 'info',
            'trim' => 'info',
            'nl2br' => 'info',
            'striptags' => 'info',
            'format' => 'info',
            'replace' => 'info',
            'escape' => 'info',
            'url_encode' => 'info',
            'convert_encoding' => 'info',
            'date' => 'success',
            'date_modify' => 'success',
            'abs' => 'warning',
            'round' => 'warning',
            'number_format' => 'warning',
            'sort' => 'primary',
            'first' => 'primary',
            'last' => 'primary',
            'keys' => 'primary',
            'slice' => 'gray',
            'reverse' => 'gray',
            'shuffle' => 'gray',
        ])
        ->and($filters['default']->getTypes())
        ->toBe(['text'])
        ->and($filters['default']->getSchema())
        ->toHaveCount(1)
        ->and($filters['upper']->supportsBlocks())
        ->toBeTrue()
        ->and($filters['trim']->getSchema())
        ->toHaveCount(1)
        ->and($filters['trim']->getArguments([
            'character_mask' => null,
            'side' => 'both',
        ]))
        ->toBe([])
        ->and($filters['trim']->getArguments([
            'character_mask' => null,
            'side' => 'left',
        ]))
        ->toBe(["side: 'left'"])
        ->and($filters['trim']->getArguments([
            'character_mask' => '.',
            'side' => 'right',
        ]))
        ->toBe(["'.'", "'right'"])
        ->and($filters['nl2br']->getTypes())
        ->toBe(['text'])
        ->and($filters['striptags']->getSchema())
        ->toHaveCount(1)
        ->and($filters['striptags']->getArguments(['allowable_tags' => null]))
        ->toBe([])
        ->and($filters['striptags']->getArguments(['allowable_tags' => '<br><p>']))
        ->toBe(["'<br><p>'"])
        ->and($filters['url_encode']->getTypes())
        ->toBe(['text'])
        ->and($filters['convert_encoding']->getSchema())
        ->toHaveCount(1)
        ->and($filters['convert_encoding']->getArguments([
            'to' => 'UTF-8',
            'from' => "ISO-'8859-1",
        ]))
        ->toBe(["'UTF-8'", "'ISO-\\'8859-1'"])
        ->and($filters['date_modify']->getTypes())
        ->toBe(['date'])
        ->and($filters['date_modify']->getArguments(['modifier' => "+1 day's time"]))
        ->toBe(["'+1 day\\'s time'"])
        ->and($filters['format']->getTypes())
        ->toBe(['text'])
        ->and($filters['format']->getArguments([
            'arguments' => ['user.name', "'Guest'", 42],
        ]))
        ->toBe(['user.name', "'Guest'", '42'])
        ->and($filters['replace']->getSchema())
        ->toHaveCount(1)
        ->and($filters['replace']->getArguments([
            'replacements' => ["'" => '’', '\\' => '/'],
        ]))
        ->toBe(["{'\\'': '’', '\\\\': '/'}"])
        ->and($filters['escape']->supportsBlocks())
        ->toBeTrue()
        ->and($filters['escape']->getArguments(['strategy' => 'html_attr']))
        ->toBe(["'html_attr'"])
        ->and($filters['abs']->getTypes())
        ->toBe(['number', 'integer'])
        ->and($filters['date']->getTypes())
        ->toBe(['date'])
        ->and($filters['date']->getArguments(['format' => 'd.m.Y']))
        ->toBe(["'d.m.Y'"])
        ->and($filters['round']->getSchema())
        ->toHaveCount(1)
        ->and($filters['round']->getArguments([
            'precision' => 2,
            'method' => 'floor',
        ]))
        ->toBe(['2', "'floor'"])
        ->and($filters['number_format']->getSchema())
        ->toHaveCount(1)
        ->and($filters['number_format']->getArguments([
            'decimals' => 2,
            'decimal_point' => ',',
            'thousands_separator' => ' ',
        ]))
        ->toBe(['2', "','", "' '"])
        ->and($filters['number_format']->getArguments([
            'decimals' => 0,
            'decimal_point' => null,
            'thousands_separator' => null,
        ]))
        ->toBe(['0'])
        ->and($filters['sort']->supports('collection'))
        ->toBeTrue()
        ->and($filters['slice']->getTypes())
        ->toBe(['text', 'collection'])
        ->and($filters['slice']->getSchema())
        ->toHaveCount(2)
        ->and($filters['slice']->getArguments([
            'offset' => -4,
            'length' => -1,
            'preserve_keys' => false,
        ]))
        ->toBe(['-4', '-1'])
        ->and($filters['slice']->getArguments([
            'offset' => 2,
            'length' => null,
            'preserve_keys' => true,
        ]))
        ->toBe(['2', 'null', 'true'])
        ->and($filters['reverse']->getTypes())
        ->toBe(['text', 'collection'])
        ->and($filters['reverse']->getOutput())
        ->toBe(FilterOutput::Same)
        ->and($filters['reverse']->getArguments(['preserve_keys' => false]))
        ->toBe([])
        ->and($filters['reverse']->getArguments(['preserve_keys' => true]))
        ->toBe(['true'])
        ->and($filters['shuffle']->getTypes())
        ->toBe(['text', 'collection'])
        ->and($filters['shuffle']->getOutput())
        ->toBe(FilterOutput::Same)
        ->and($filters['first']->getOutput())
        ->toBe(FilterOutput::CollectionItem)
        ->and($filters['last']->getOutput())
        ->toBe(FilterOutput::CollectionItem)
        ->and($filters['keys']->getIcon())
        ->not->toBeNull();
});

it('uses compact filter layouts and native selects', function () {
    $filters = collect(BuiltInCatalog::filters())->keyBy->getName();

    foreach ([
        'trim' => 2,
        'convert_encoding' => 2,
        'round' => 2,
        'number_format' => 3,
        'slice' => 2,
    ] as $filterName => $columns) {
        $group = $filters[$filterName]->getSchema()[0];

        expect($group)->toBeInstanceOf(Group::class)->and($group->getColumns('lg'))->toBe($columns);
    }

    foreach (['trim', 'round'] as $filterName) {
        $group = $filters[$filterName]->getSchema()[0];
        $select = collect($group->getDefaultChildComponents())
            ->first(static fn (mixed $component) => $component instanceof Select);

        expect($select)
            ->toBeInstanceOf(Select::class)
            ->and($select->isNative())
            ->toBeTrue()
            ->and($select->isSearchable())
            ->toBeFalse();
    }

    foreach (['escape', 'date'] as $filterName) {
        $select = $filters[$filterName]->getSchema()[0];

        expect($select)
            ->toBeInstanceOf(Select::class)
            ->and($select->isNative())
            ->toBeTrue()
            ->and($select->isSearchable())
            ->toBeFalse();
    }
});

it('provides the built-in Twig conditions', function () {
    $conditions = collect(BuiltInCatalog::conditions())->keyBy->getName();

    expect($conditions->keys()->all())
        ->toBe([
            'equals',
            'defined',
            'empty',
            'null',
            'greater_than',
            'at_least',
            'less_than',
            'at_most',
            'even',
            'odd',
            'in',
            'sequence',
            'mapping',
        ])
        ->and($conditions->map->getColor()->all())
        ->toBe([
            'equals' => 'gray',
            'defined' => 'gray',
            'empty' => 'gray',
            'null' => 'gray',
            'greater_than' => 'warning',
            'at_least' => 'warning',
            'less_than' => 'warning',
            'at_most' => 'warning',
            'even' => 'warning',
            'odd' => 'warning',
            'in' => 'primary',
            'sequence' => 'primary',
            'mapping' => 'primary',
        ])
        ->and($conditions['equals']->getType())
        ->toBe(ConditionKind::Comparison)
        ->and($conditions['equals']->getExpression())
        ->toBe('==')
        ->and($conditions['equals']->getTypes())
        ->toBe(['text', 'number', 'integer', 'boolean'])
        ->and($conditions['greater_than']->getTypes())
        ->toBe(['number', 'integer'])
        ->and($conditions['in']->matchesVariableTypes())
        ->toBeFalse()
        ->and($conditions['defined']->getType())
        ->toBe(ConditionKind::Test)
        ->and($conditions['null']->getTypes())
        ->toBe([])
        ->and($conditions['even']->getTypes())
        ->toBe(['number', 'integer'])
        ->and($conditions['odd']->getExpression())
        ->toBe('odd')
        ->and($conditions['sequence']->getTypes())
        ->toBe(['collection'])
        ->and($conditions->map->getDescription()->filter())
        ->toHaveCount($conditions->count());
});

it('only exposes filters and tests provided by the installed Twig version', function () {
    $twig = new Environment(new ArrayLoader);

    foreach (BuiltInCatalog::filters() as $filter) {
        expect($twig->getFilter($filter->getName()))->not->toBeNull();
    }

    foreach (BuiltInCatalog::conditions() as $condition) {
        if ($condition->getType() === ConditionKind::Test) {
            expect($twig->getTest($condition->getExpression()))->not->toBeNull();
        }
    }
});

it('serializes replace configuration as a valid Twig mapping', function () {
    $filter = collect(BuiltInCatalog::filters())->keyBy->getName()->get('replace');
    $arguments = implode(', ', $filter->getArguments([
        'replacements' => ["'" => '’', '\\' => '/'],
    ]));
    $twig = new Environment(new ArrayLoader);

    expect($twig->createTemplate("{{ value|replace({$arguments}) }}")->render([
        'value' => "It's \\ here",
    ]))
        ->toBe('It’s / here');
});

it('serializes an omitted slice length with preserved keys', function () {
    $filter = collect(BuiltInCatalog::filters())->keyBy->getName()->get('slice');
    $arguments = implode(', ', $filter->getArguments([
        'offset' => 2,
        'length' => null,
        'preserve_keys' => true,
    ]));
    $twig = new Environment(new ArrayLoader);

    expect($twig->createTemplate(
        "{% for key, value in values|slice({$arguments}) %}{{ key }}:{{ value }};{% endfor %}",
    )->render([
        'values' => [10, 20, 30, 40],
    ]))
        ->toBe('2:30;3:40;');
});

it('serializes a trim side without requiring a character mask', function () {
    $filter = collect(BuiltInCatalog::filters())->keyBy->getName()->get('trim');
    $arguments = implode(', ', $filter->getArguments([
        'character_mask' => null,
        'side' => 'left',
    ]));
    $twig = new Environment(new ArrayLoader);

    expect($twig->createTemplate("{{ value|trim({$arguments}) }}")->render([
        'value' => '  Keep right  ',
    ]))
        ->toBe('Keep right  ');
});
