<?php

namespace PHPinnacle\Rosetta\Tests;

use PHPinnacle\Rosetta\Enums\FieldType;
use PHPinnacle\Rosetta\Fields\NumberField;
use PHPinnacle\Rosetta\Fields\ReferenceField;
use PHPinnacle\Rosetta\Fields\ScalarField;
use PHPinnacle\Rosetta\Fields\StringField;
use PHPinnacle\Rosetta\Fields\UnionField;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FieldsTest extends TestCase
{
    #[Test]
    public function fields_expose_their_reference_targets(): void
    {
        $field = new UnionField([
            new ScalarField(FieldType::Boolean),
            new ReferenceField('first-target'),
            new UnionField([
                new ReferenceField('second-target'),
                new ReferenceField('first-target'),
            ]),
        ]);

        $this->assertSame(['first-target', 'second-target'], $field->targets());
        $this->assertSame([], new StringField(length: 50, fixed: false)->targets());
    }

    #[Test]
    public function it_serializes_specialized_field_implementations(): void
    {
        $fields = [
            new ScalarField(FieldType::Id),
            new ScalarField(FieldType::Boolean),
            new StringField(length: 50, fixed: false),
            new NumberField(precision: 15, scale: 2, unsigned: true),
            new ReferenceField('254a3240-79c0-4a3f-9259-5122c9ddd71b'),
            new UnionField([
                new ScalarField(FieldType::Boolean),
                new StringField(length: 10, fixed: true),
            ]),
        ];

        $this->assertSame(
            [
                ['type' => 'id'],
                ['type' => 'boolean'],
                ['type' => 'string', 'length' => 50, 'fixed' => false],
                ['type' => 'number', 'precision' => 15, 'scale' => 2, 'unsigned' => true],
                ['type' => 'reference', 'target' => '254a3240-79c0-4a3f-9259-5122c9ddd71b'],
                [
                    'type' => 'union',
                    'variants' => [
                        ['type' => 'boolean'],
                        ['type' => 'string', 'length' => 10, 'fixed' => true],
                    ],
                ],
            ],
            json_decode(json_encode($fields, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR),
        );
    }
}
