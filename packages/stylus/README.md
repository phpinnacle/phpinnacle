# Stylus for Filament

[![Latest Version on Packagist](https://img.shields.io/packagist/v/phpinnacle/stylus.svg?style=flat-square)](https://packagist.org/packages/phpinnacle/stylus)

Stylus extends Filament's rich editor for structured editorial content. It adds semantic HTML elements and a description-list editing experience while keeping the result compatible with Filament's TipTap-based editor.

## Features

- `RichEditor` with packaged TipTap extensions.
- `ContentEditor` preset for editorial forms.
- Semantic `article`, `section` and `div` nodes.
- Description lists with `dl`, `dt` and `dd` nodes.
- Conditional regions for block content and inline text.
- Filament `TwigEditor` for structured Twig templates.
- HTML extension and description-list JavaScript plugins.
- `HtmlFormatter` support for rendering stored content.

## Installation

```bash
composer require phpinnacle/stylus
php artisan filament:assets
```

Livewire 4 limits dot-notation property paths to 10 levels by default, while TipTap JSON uses a `content` level for every nested document node. Publish the Livewire configuration and raise the finite limit to `20` so tables and nested Twig structures can be edited:

```bash
php artisan livewire:publish --config
```

```php
// config/livewire.php
'payload' => [
    // Keep the other Livewire payload guards unchanged.
    'max_nesting_depth' => 20,
],
```

## Form usage

```php
use PHPinnacle\Stylus\Forms\ContentEditor;
use PHPinnacle\Stylus\Forms\RichEditor;

ContentEditor::make('content')->required();

RichEditor::make('description');
```

The service provider registers the compiled editor extensions and CSS. Run `php artisan filament:assets` after installing or updating the package. Package maintainers can rebuild `resources/dist` with the scripts declared in `package.json`; consuming applications normally use the distributed assets.

Stored content is HTML. Apply the same trust, sanitization and authorization rules used for any user-authored HTML before rendering it outside trusted administration screens.

Block conditions are stored as `<div data-condition="...">`, while inline conditions use `<span data-condition="...">`. Stylus treats each condition as an opaque string: the consuming application is responsible for interpreting it and deciding whether the associated content should be rendered. Never evaluate user-authored conditions as PHP or JavaScript code.

## Twig editor

`TwigEditor` extends Filament's `RichEditor` with Twig variables, filters, block and inline conditions, and `for` loops. The field uses ProseMirror JSON while editing and dehydrates to a versioned document with a render-ready Twig template:

```php
public array $template = [
    'version' => 1,
    'document' => [
        'type' => 'doc',
        'content' => [
            [
                'type' => 'paragraph',
                'content' => [
                    ['type' => 'text', 'text' => 'Hello '],
                    [
                        'type' => 'twigVariable',
                        'attrs' => [
                            'expression' => 'user.name',
                            'filters' => [],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'template' => '<p>Hello {{ user.name }}</p>',
];
```

Use the editor as a Filament form field:

```php
use Filament\Support\Icons\Heroicon;
use PHPinnacle\Stylus\BuiltInCatalog;
use PHPinnacle\Stylus\Forms\TwigEditor;
use PHPinnacle\Stylus\Snippet;
use PHPinnacle\Stylus\Variable;

TwigEditor::make('template')
    ->variables(
        Variable::make('user', 'user')
            ->label('User')
            ->icon(Heroicon::User)
            ->description('Customer profile fields')
            ->color('info')
            ->group('User')
            ->properties(
                Variable::text('name')->label('User name')->sample('Ada Lovelace'),
                Variable::text('email')->label('User email'),
            ),
        Variable::collection('orders')
            ->label('Orders')
            ->items(
                Variable::make('order', 'order')
                    ->properties(
                        Variable::text('number')->label('Order number'),
                        Variable::collection('lines')->items(
                            Variable::make('line', 'line')->properties(
                                Variable::text('description'),
                                Variable::make('amount', 'number'),
                            ),
                            keyType: 'integer',
                        ),
                    ),
                keyType: 'integer',
            ),
    )
    ->filters(...BuiltInCatalog::filters())
    ->conditions(...BuiltInCatalog::conditions())
    ->snippets(
        Snippet::make('account_signature', [[
            'type' => 'paragraph',
            'content' => [
                ['type' => 'text', 'text' => 'Kind regards, '],
                [
                    'type' => 'twigVariable',
                    'attrs' => ['expression' => 'user.name', 'filters' => []],
                ],
            ],
        ]])
            ->label('Account signature')
            ->icon(Heroicon::User)
            ->description('Reusable customer sign-off')
            ->color('info')
            ->requires('user.name'),
    )
    ->required()
    ->columnSpanFull();
```

`BuiltInCatalog::filters()` provides common text, date, numeric, and collection filters, including fallback values, casing, trimming, line-break conversion, HTML tag removal, contextual escaping, URL and character encoding, date formatting and modification, printf-style formatting, text replacement, numeric formatting, sorting, slicing, reversing, shuffling, first and last items, and keys. `BuiltInCatalog::conditions()` provides equality, ordering, membership, existence, emptiness, null, parity, sequence, and mapping rules. Built-in descriptor colors consistently identify text, date, numeric, collection, and cross-type operations. The catalog is opt-in so each application still controls its allowed Twig vocabulary. Pass custom descriptors after the built-ins in the same `filters()` or `conditions()` call to replace an entry with the same name or condition key.

The provided `Variable`, `Filter`, and `Condition` value objects implement their matching `PHPinnacle\Stylus\Contracts` interfaces. `TwigEditor` accepts those contracts, so applications may provide alternative descriptor implementations. The contracts also extend Filament's `HasLabel`, `HasIcon`, `HasDescription`, and `HasColor` interfaces. `Snippet` implements the same Filament presentation interfaces directly. Configure the provided objects with `label()`, `icon()`, `description()`, and `color()` just like other Filament objects. Focused filter and condition-rule modals reuse the descriptor icon, color, and description in their headers.

Use `types()` to limit a filter or condition to compatible variable types. An empty type list supports every registered variable type. The default `Condition::truthy()` descriptor only offers boolean variables and may be replaced through `conditions()` like any other condition descriptor. Comparisons match variable operands by type, including when their type list is empty. Call `matchVariableTypes(false)` for asymmetric operators such as `in`, whose two operands intentionally have different types.

The variables panel only inserts registered variables. It searches by label, description, expression, group, and type, and keeps per-field favorites and the five most recently inserted variables in browser storage without adding them to the template document. A variable may define a visual-only `sample()` value; the panel toggle switches variable chips between their labels and samples without changing the stored expression. Clicking an inserted variable opens its filter pipeline in a side panel, where filters can be added, removed, and reordered, and the variable itself can be deleted. Filters with a non-empty `schema()` expose a configuration button that opens a modal for that filter only. Filtered chips show the first filter and the number of additional filters, while their tooltip keeps the complete Twig expression. Variables with nested `properties()` are expandable groups rather than insertable values, while their leaf properties remain insertable. Selecting a collection inserts a `for item in collection` block with no key; scalar leaf variables remain inline. Variables may be grouped and carry a type. Collections declare their item shape and key type with `items()`. Inside a `for` block, the panel derives the item, key, nested properties, and `loop.*` variables from the collection selected in that loop. Nested loops also expose their parent through expressions such as `loop.parent.loop.index`. `loop.length`, `loop.last`, and reverse indexes require a countable iterable. Outer item variables remain available in nested loops, while loop variables never leak outside their lexical scope.

Registered filters are offered only when their `types()` include the selected variable type; an empty type list makes a filter available to every variable. Each filter may provide a Filament `schema()` for its arguments. By default, non-empty scalar schema values are serialized as positional Twig arguments in schema order. Use `argumentsUsing()` when the form state needs custom conversion; its callback must return a list of Twig expression strings.

Call `blocks()` on a filter to also expose it for a selected block region. The toolbar opens the block-filter catalog in a side panel; choosing the first filter wraps the current region in a `twigApply` node and serializes its ordered filter chain with Twig's `{% apply %}` tag. Clicking an existing apply block opens the same panel with its sortable filter pipeline, keep-content action, and delete action. Configurable block filters open a focused modal for that filter only. Scalar-only filters never appear in the block picker.

Filters whose `types()` support `collection` and preserve a collection result are available in every block, row, or cell loop. Untyped filters support collections along with every other variable type. Clicking an existing block loop opens a side panel for its item and key variables, empty-state branch, sortable filter pipeline, keep-content action, and delete action while keeping the base iterable read-only. Configurable collection filters use the same focused filter modal as variable pipelines. The filter chain is shown next to the loop on the canvas and is serialized after the iterable, such as `orders|sort|slice(0, 10)`. Use `output(FilterOutput::CollectionItem)` for filters such as `first` and `last`; they remain available for collection values but are excluded from loop pipelines because their result is an item rather than an iterable.

Structured conditions store a small expression AST and compile it from the variables in the current lexical scope plus the operators and tests registered by the application. The toolbar condition picker exposes each registered operator and test directly; selecting `Equals`, `Is contained in`, or `Defined` opens a focused insertion modal for that rule's operands and inserts a single non-negated rule. Authors can add more rules, change the group operator, negate the condition, or add an `else` branch later from the condition side panel. Clicking a rule drills into its operands in the same panel. Variable operands have their own ordered filter pipeline there; type-preserving filters are available, while filters that return a collection item are excluded. Literal value choices follow the selected variable type; exact null checks use the dedicated `Null` rule. Only a filter's own parameter form opens as a focused modal. Descriptor names and Twig operator expressions are trusted developer configuration; Stylus validates the condition values chosen or entered by the editor user.

Register application-provided structured fragments with `snippets()`. The snippets panel searches the catalog by label, name, and required variable, and inserts a detached copy of the selected ProseMirror content, so later catalog changes do not mutate existing templates. Declare every local dependency with `requires()`; unavailable snippets remain visible with their missing variables and cannot be inserted until all requirements exist in the current lexical scope. Snippet definitions are trusted developer configuration and should contain nodes supported by the editor schema.

Read the generated template from dehydrated form state:

```php
$state = $this->form->getState();
$template = $state['template']['template'];
```

Run `php artisan filament:assets` after installing or updating the package. The field loads its Twig extension through Filament's asset system only when it is rendered. Variable insertion, snippet insertion, block-filter insertion, variable filters, existing apply blocks, existing block loop settings, and existing condition settings use responsive panels inside the editor, while insertion forms and focused filter configuration use Filament action modals. Side panels share a resizable desktop width that is remembered per field in browser storage. The canvas uses compact template marks that reveal full expressions on interaction; the `Template structure` toolbar toggle pins those details open and remembers the preference per field. Standalone Livewire form components must therefore implement Filament's `HasActions` contract and render `<x-filament-actions::modals />`; Filament panels already provide this integration.

The condition-branch toolbar control switches block and inline conditions between showing both branches, only the `if` branch, or only the `else` branch. This display preference is remembered per field and does not change the stored template document.

The initial schema supports paragraphs, headings from `h1` through `h6`, editable tables, inline variables with ordered filters, block filter regions, block and inline `if`/`else` conditions, and `for` loops with optional empty-state branches. Insert condition first shows the available rules in a toolbar picker, then creates a block condition from the configured rule by default or an inline condition when text within one inline container is selected. Inline conditions may be nested when the selection stays within one existing condition branch. A multi-block selection is wrapped in the block condition without replacing its content. Every inline condition mark stores an explicit shared `conditionId` and a `then` or `else` branch; documents using the earlier implicit single-branch format are not supported. Clicking an existing block or inline condition opens a side panel for its matching mode, group negation, sortable rules, optional `else` branch, keep-content action, and delete action. Adding and editing rules use the same panel, with the selected variable operand's filters shown directly below its fields. The template outline derives a compact tree of conditions, loops, table loops, and variables from the live document; selecting an entry focuses the corresponding editor node without persisting a second tree. Table tools can add or remove rows and columns, repeat a row vertically, repeat a contiguous group of cells horizontally, condition the current row or a full contiguous row selection, toggle the header row, or delete the table. A row condition uses the same focused rule picker and condition side panel, preserves all selected cells, and serializes as one Twig `if` around the affected `<tr>` elements. Its toolbar control opens the rule picker for an unconditioned row, opens the condition settings directly for an existing condition, and shows the number of configured rules. Repeat-row and repeat-cells tools open the shared loop side panel with the collections available in the current scope; choosing one creates the loop and switches the same panel to its item, key, sortable-filter, and stop-repeating controls. A repeat tool is disabled when its selected row or cells already have the corresponding loop. Structured rows carry separate layout-neutral `IF` and `FOR` chips at their top edge. Each chip expands to its matching expression on hover, while cell loops keep a separate inline annotation. Configurable filters still use their focused modal. Both use the variables available in their lexical scope, and merged cells cannot be combined with table loops. Block loops store their content in explicit body and optional empty-state branches.

Validate generated templates with the application's configured Twig environment so custom filters and functions are recognized:

```php
use PHPinnacle\Stylus\Twig\TwigTemplateValidator;

$result = (new TwigTemplateValidator($twig))->validate($template['template']);
```

Syntax validation does not authorize or sandbox a template. Apply an appropriate Twig sandbox policy when template authors are not fully trusted.

## Testing

Run the package checks from the monorepo root:

```bash
php artisan test --compact packages/phpinnacle/stylus/tests
(cd packages/phpinnacle/stylus && ../../../vendor/bin/phpstan analyse -c phpstan.neon.dist --no-progress)
npm --prefix packages/phpinnacle/stylus run build
```

## Changelog and license

See [CHANGELOG](CHANGELOG.md). Released under the [MIT License](LICENSE.md).
