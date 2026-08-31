<?php

namespace PHPinnacle\Stylus;

use PHPinnacle\Stylus\Contracts\Variable as VariableContract;

class VariableScope
{
    /** @var array<string, VariableContract> */
    private array $variables;

    /** @var array<string, VariableContract> */
    private array $localVariables = [];

    /** @param array<string, VariableContract> $variables */
    public function __construct(array $variables)
    {
        $this->variables = $variables;
    }

    public function enterLoop(string $item, ?string $key, string $iterable, string $group): void
    {
        $iterableVariable = $this->variables[$iterable] ?? null;
        $itemVariable = $iterableVariable?->getItem() ?? Variable::make($item, 'mixed');
        $parentLoop = $this->variables['loop'] ?? null;

        $this->replaceBinding($item, $itemVariable->flatten($item, $group));

        if (filled($key)) {
            $this->replaceBinding($key, [
                $key => Variable::make($key, $iterableVariable?->getKeyType() ?? 'mixed')->group($group),
            ]);
        }

        $this->replaceBinding('loop', $this->makeLoopVariable($parentLoop)->flatten(group: $group));
    }

    /** @return array<string, string> */
    public function getIterableOptions(): array
    {
        $options = [];

        foreach ($this->variables as $variable) {
            if (!$variable->isCollection()) {
                continue;
            }

            $options[$variable->getName()] =
                ($variable->getLabel() ?? $variable->getName()) . ' — ' . $variable->getName();
        }

        return $options;
    }

    /** @return array<string, VariableContract> */
    public function getLocalVariables(): array
    {
        return $this->localVariables;
    }

    public function getVariable(string $name): ?VariableContract
    {
        return $this->variables[$name] ?? null;
    }

    /**
     * @param  list<string>  $types
     * @return array<string, string>
     */
    public function getVariableOptions(array $types = []): array
    {
        $options = [];

        foreach ($this->variables as $variable) {
            if ($types !== [] && !in_array($variable->getType(), $types, true)) {
                continue;
            }

            $options[$variable->getName()] =
                ($variable->getLabel() ?? $variable->getName()) . ' — ' . $variable->getName();
        }

        return $options;
    }

    /** @return array<string, VariableContract> */
    public function getVariables(): array
    {
        return $this->variables;
    }

    private function makeLoopVariable(?VariableContract $parentLoop): VariableContract
    {
        $properties = [
            Variable::make('index', 'integer')->label(__('phpinnacle-stylus::forms.twig_editor.panel.index')),
            Variable::make('index0', 'integer')->label(__('phpinnacle-stylus::forms.twig_editor.panel.index0')),
            Variable::make('revindex', 'integer')->label(__('phpinnacle-stylus::forms.twig_editor.panel.revindex')),
            Variable::make('revindex0', 'integer')->label(__('phpinnacle-stylus::forms.twig_editor.panel.revindex0')),
            Variable::make('first', 'boolean')->label(__('phpinnacle-stylus::forms.twig_editor.panel.first')),
            Variable::make('last', 'boolean')->label(__('phpinnacle-stylus::forms.twig_editor.panel.last')),
            Variable::make('length', 'integer')->label(__('phpinnacle-stylus::forms.twig_editor.panel.length')),
        ];

        if ($parentLoop) {
            $properties[] = Variable::make('parent', 'context')
                ->label(__('phpinnacle-stylus::forms.twig_editor.panel.parent'))
                ->properties($parentLoop);
        }

        return Variable::make('loop', 'loop')
            ->label(__('phpinnacle-stylus::forms.twig_editor.panel.loop'))
            ->properties(...$properties);
    }

    /** @param array<string, VariableContract> $variables */
    private function replaceBinding(string $name, array $variables): void
    {
        foreach (array_keys($this->variables) as $variableName) {
            if ($variableName === $name || str_starts_with($variableName, "{$name}.")) {
                unset($this->variables[$variableName], $this->localVariables[$variableName]);
            }
        }

        foreach ($variables as $variableName => $variable) {
            $this->variables[$variableName] = $variable;
            $this->localVariables[$variableName] = $variable;
        }
    }
}
