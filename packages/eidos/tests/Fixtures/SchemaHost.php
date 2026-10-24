<?php

namespace PHPinnacle\Eidos\Tests\Fixtures;

use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Livewire\Component;

final class SchemaHost extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    /** @var array<string, mixed> */
    public array $data = [];

    public static function make(): self
    {
        $component = new self;
        $component->setId('eidos-schema-test');
        $component->setName('eidos-schema-test');

        return $component;
    }
}
