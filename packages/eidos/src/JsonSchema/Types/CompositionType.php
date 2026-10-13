<?php

namespace PHPinnacle\Eidos\JsonSchema\Types;

use Illuminate\JsonSchema\Types\AnyOfType;
use Illuminate\JsonSchema\Types\Type;
use PHPinnacle\Eidos\JsonSchema\Schema;

abstract class CompositionType extends AnyOfType
{
    /** @param list<Type> $schemas */
    public function __construct(
        array $schemas,
        private readonly ?Type $sibling = null,
    ) {
        parent::__construct($schemas);
    }

    /** @return 'oneOf'|'allOf' */
    abstract public function keyword(): string;

    public function sibling(): ?Type
    {
        return $this->sibling;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $definition = get_object_vars($this);
        unset($definition['schemas'], $definition['required'], $definition['nullable'], $definition['sibling']);
        $definition = array_filter($definition, fn (mixed $value) => $value !== null);
        $branches = array_map($this->serialize(...), $this->schemas());

        return $this->compose(
            $definition,
            $branches,
            $this->sibling === null ? null : $this->serialize($this->sibling),
        );
    }

    /**
     * @param array<string, mixed> $definition
     * @param list<array<string, mixed>> $branches
     * @param array<string, mixed>|null $sibling
     * @return array<string, mixed>
     */
    public function compose(array $definition, array $branches, ?array $sibling): array
    {
        unset($definition['anyOf'], $definition['sibling']);
        $definition[$this->keyword()] = $branches;

        if ($sibling !== null) {
            $definition = array_replace($definition, $sibling);
        }

        return $this->nullable ? ['anyOf' => [$definition, ['type' => 'null']]] : $definition;
    }

    /** @return array<string, mixed> */
    private function serialize(Type $schema): array
    {
        return ($schema instanceof Schema ? $schema : new Schema($schema))->toArray();
    }
}
