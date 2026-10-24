<?php

namespace PHPinnacle\Common\Forms\InlineBuilder;

use Filament\Actions\Action;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PHPinnacle\Common\Forms\InlineBuilder;

/** @phpstan-import-type BuilderItem from InlineBuilder */
class BuilderAction
{
    public static function make(InlineBuilder $field): Action
    {
        $available = $field->getBlocks();
        $selected = fn (array $arguments) => self::selected($arguments, $available);

        return Action::make($field->getEditorActionName())
            ->label($field->getAddActionLabel())
            ->icon(Heroicon::Plus)
            ->color('gray')
            ->disabled(
                fn (InlineBuilder $component, array $arguments) => (
                    $component->isDisabled()
                    || !self::isAvailable($arguments, $available)
                    || array_key_exists('order', $arguments) && !$component->isReorderable()
                    || array_key_exists('remove', $arguments) && !$component->isDeletable()
                    || self::isAdding($arguments)
                    && is_string($arguments['block'] ?? null)
                    && !array_key_exists($arguments['block'], $component->getAvailableBlocks())
                ),
            )
            ->view('phpinnacle-common::components.inline-builder-action')
            ->viewData(fn (InlineBuilder $component) => [
                'available' => $available,
                'addable' => $component->getAvailableBlocks(),
                'settings' => $component->getItems(),
                'allowedMoves' => $component->getAllowedMoves(),
            ])
            ->modalHidden(
                fn (Action $action, array $arguments) => (
                    $action->isDisabled()
                    || !self::hasForm($arguments)
                    || $selected($arguments)->getSchema($field, self::position($field->getItems(), $arguments)) === []
                ),
            )
            ->modalIcon(fn (array $arguments) => $selected($arguments)->getIcon())
            ->modalDescription(fn (array $arguments) => $selected($arguments)->getDescription())
            ->modalHeading(function (InlineBuilder $component, array $arguments) use ($selected) {
                $label = $component->getLabel();

                return __('phpinnacle-common::inline-builder.heading', [
                    'block' => $selected($arguments)->getLabel(),
                    'field' => strip_tags($label instanceof Htmlable ? $label->toHtml() : $label ?? ''),
                ]);
            })
            ->schema(function (Action $action, InlineBuilder $component, array $arguments) use ($selected) {
                if ($action->isDisabled() || !self::hasForm($arguments)) {
                    return [];
                }

                return self::schema(
                    $selected($arguments),
                    $component,
                    self::position($component->getItems(), $arguments),
                );
            })
            ->fillForm(fn (InlineBuilder $component, array $arguments) => self::hasForm($arguments)
                ? self::formData($component->getItems(), $arguments)
                : null)
            ->action(function (InlineBuilder $component, array $arguments, array $data) use ($available) {
                if (self::isAdding($arguments)) {
                    Validator::make($arguments, [
                        'block' => [
                            'required',
                            Rule::in(array_keys($component->getAvailableBlocks())),
                        ],
                    ])->validate();
                }
                /** @var array{data?: array<string, mixed>} $data */
                $settings = self::apply($component->getItems(), $arguments, $data, $available);
                if (array_key_exists('order', $arguments) && !$component->isOrderAllowed($settings)) {
                    throw ValidationException::withMessages([
                        'order' => __('phpinnacle-common::inline-builder.order'),
                    ]);
                }
                $component->state($settings);
                $component->callAfterStateUpdated();
            });
    }

    /** @param array<array-key, mixed> $arguments */
    private static function hasForm(array $arguments): bool
    {
        return !array_key_exists('order', $arguments) && !array_key_exists('remove', $arguments);
    }

    /** @param array<array-key, mixed> $arguments */
    private static function isAdding(array $arguments): bool
    {
        return self::hasForm($arguments) && !array_key_exists('index', $arguments);
    }

    /**
     * @param list<BuilderItem> $settings
     * @param array<array-key, mixed> $arguments
     * @param array{data?: array<string, mixed>} $data
     * @param array<string, Block> $available
     * @return list<BuilderItem>
     */
    private static function apply(array $settings, array $arguments, array $data, array $available): array
    {
        if (array_key_exists('order', $arguments)) {
            return self::reorder($settings, $arguments);
        }

        $key = self::selected($arguments, $available)->getName();

        $position = self::position($settings, $arguments);

        if ($position === null) {
            $settings[] = ['type' => $key, 'data' => $data['data'] ?? []];
        } elseif (array_key_exists('remove', $arguments)) {
            unset($settings[$position]);
        } else {
            $settings[$position] = ['type' => $key, 'data' => $data['data'] ?? []];
        }

        return array_values($settings);
    }

    /**
     * @param list<BuilderItem> $settings
     * @param array<array-key, mixed> $arguments
     * @return array{data: array<string, mixed>}|null
     */
    private static function formData(array $settings, array $arguments): ?array
    {
        $position = self::position($settings, $arguments);

        return $position === null ? null : ['data' => $settings[$position]['data'] ?? []];
    }

    /**
     * @param list<BuilderItem> $settings
     * @param array<array-key, mixed> $arguments
     */
    private static function position(array $settings, array $arguments): ?int
    {
        $matching = array_filter(
            $settings,
            static fn (array $setting) => $setting['type'] === ($arguments['block'] ?? null),
        );
        /** @var array{index?: int|string} $validated */
        $validated = Validator::make($arguments, [
            'index' => [
                Rule::requiredIf(array_key_exists('remove', $arguments)),
                'integer',
                Rule::in(array_keys($matching)),
            ],
        ])->validate();

        return array_key_exists('index', $validated) ? (int) $validated['index'] : null;
    }

    /** @return list<Component> */
    private static function schema(Block $block, InlineBuilder $component, ?int $index): array
    {
        $schema = $block->getSchema($component, $index);

        return (
            $schema === []
                ? []
                : [Group::make()->statePath('data')->schema($schema)]
        );
    }

    /**
     * @param array<array-key, mixed> $arguments
     * @param array<string, Block> $available
     */
    private static function isAvailable(array $arguments, array $available): bool
    {
        return (
            array_key_exists('order', $arguments)
            || is_string($arguments['block'] ?? null) && array_key_exists($arguments['block'], $available)
        );
    }

    /**
     * @param list<BuilderItem> $settings
     * @param array<array-key, mixed> $arguments
     * @return list<BuilderItem>
     */
    private static function reorder(array $settings, array $arguments): array
    {
        /** @var array{order: list<int|string>} $validated */
        $validated = Validator::make($arguments, [
            'order' => ['present', 'array', 'list', 'size:' . count($settings)],
            'order.*' => ['required', 'integer', 'distinct', Rule::in(array_keys($settings))],
        ])->validate();

        return array_map(static fn (int|string $index) => $settings[(int) $index], $validated['order']);
    }

    /**
     * @param array<array-key, mixed> $arguments
     * @param array<string, Block> $available
     */
    private static function selected(array $arguments, array $available): Block
    {
        /** @var array{block: string} $validated */
        $validated = Validator::make($arguments, [
            'block' => ['required', 'string', Rule::in(array_keys($available))],
        ])->validate();

        return $available[$validated['block']];
    }
}
