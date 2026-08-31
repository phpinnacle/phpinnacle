<?php

namespace PHPinnacle\Porta\Contracts;

use Filament\Support\Contracts\HasLabel;

interface Handler extends HasLabel
{
    public function getType(): string;

    public function getExample(): ?string;

    public function process(array $payload): void;
}
