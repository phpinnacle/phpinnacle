<?php

namespace PHPinnacle\Stylus\Contracts;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

interface Variable extends HasColor, HasDescription, HasIcon, HasLabel
{
    public function getName(): string;

    public function getType(): string;

    public function getGroup(): ?string;

    /** @return array<string, Variable> */
    public function getProperties(): array;

    public function getItem(): ?Variable;

    public function getKeyType(): string;

    public function getSample(): ?string;

    public function isCollection(): bool;

    /** @return array<string, Variable> */
    public function flatten(?string $name = null, ?string $group = null): array;

    public function rebase(string $name, ?string $group = null): Variable;

    /** @return array<string, mixed> */
    public function toArray(): array;
}
