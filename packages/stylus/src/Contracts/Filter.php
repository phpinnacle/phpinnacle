<?php

namespace PHPinnacle\Stylus\Contracts;

use Filament\Schemas\Components\Component;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use PHPinnacle\Stylus\Enums\FilterOutput;

interface Filter extends HasColor, HasDescription, HasIcon, HasLabel
{
    /**
     * @param  array<string, mixed>  $configuration
     * @return list<string>
     */
    public function getArguments(array $configuration): array;

    public function getName(): string;

    public function getOutput(): FilterOutput;

    /** @return list<Component> */
    public function getSchema(): array;

    /** @return list<string> */
    public function getTypes(): array;

    public function supports(string $variableType): bool;

    public function supportsBlocks(): bool;
}
