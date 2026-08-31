<?php

namespace PHPinnacle\Stylus\Contracts;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use PHPinnacle\Stylus\Enums\ConditionKind;

interface Condition extends HasColor, HasDescription, HasIcon, HasLabel
{
    public function getExpression(): string;

    public function getKey(): string;

    public function getName(): string;

    public function getType(): ConditionKind;

    /** @return list<string> */
    public function getTypes(): array;

    public function matchesVariableTypes(): bool;

    public function supports(string $variableType): bool;
}
