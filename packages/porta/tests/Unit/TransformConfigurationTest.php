<?php

namespace PHPinnacle\Porta\Tests\Unit;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use PHPinnacle\Porta\Enums\TransformType;
use PHPinnacle\Porta\Resources\Integrations\Schemas\TransformConfiguration;
use Tests\TestCase;

final class TransformConfigurationTest extends TestCase
{
    public function test_it_builds_the_selected_transformation_form(): void
    {
        $map = new TransformConfiguration(TransformType::MAP)->form();
        $cast = new TransformConfiguration(TransformType::CAST)->form();

        $this->assertInstanceOf(KeyValue::class, $map[0]);
        $this->assertSame('fields', $map[0]->getName());
        $this->assertTrue($map[0]->isRequired());
        $this->assertInstanceOf(Select::class, $cast[0]);
        $this->assertSame('string', $cast[0]->getDefaultState());
        $this->assertSame(['boolean', 'integer', 'double', 'string'], array_keys($cast[0]->getOptions()));
        $this->assertSame([], TransformType::DROP->form());
    }

    public function test_it_renders_transformation_previews(): void
    {
        $map = new TransformConfiguration(TransformType::MAP)->preview(['fields' => ['old' => 'new']]);
        $cast = new TransformConfiguration(TransformType::CAST)->preview(['type' => 'integer']);
        $date = new TransformConfiguration(TransformType::DATE)->preview([
            'input' => 'Y-m-d',
            'output' => 'd.m.Y',
        ]);

        $this->assertStringContainsString('old => new', $map);
        $this->assertStringContainsString('fi-color-success', $map);
        $this->assertStringContainsString('integer', $cast);
        $this->assertStringContainsString('fi-color-warning', $cast);
        $this->assertStringContainsString('Y-m-d => d.m.Y', $date);
        $this->assertSame($date, TransformType::DATE->preview(['input' => 'Y-m-d', 'output' => 'd.m.Y']));
    }
}
