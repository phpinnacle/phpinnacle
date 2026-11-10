<?php

namespace PHPinnacle\Eidos\Forms;

use PHPinnacle\Jason\Schema;

final readonly class SchemaCompatibility
{
    private function __construct(
        public bool $checkFormats = true,
        public bool $checkStringLengths = true,
    ) {}

    public static function make(): self
    {
        return new self;
    }

    public function formats(bool $condition = true): self
    {
        return new self($condition, $this->checkStringLengths);
    }

    public function stringLengths(bool $condition = true): self
    {
        return new self($this->checkFormats, $condition);
    }

    public function accepts(Schema $source, Schema $target): bool
    {
        $source = $source->resolveReferences();
        $target = $target->resolveReferences();

        if ($source->deniesAny() || $target->deniesAny()) {
            return false;
        }

        if ($target->allowsAny() || $target->isEmpty()) {
            return true;
        }

        $alternatives = $source->alternatives();

        if ($alternatives !== []) {
            $alternatives = array_values(array_filter($alternatives, fn (Schema $branch) => !$branch->deniesAny()));

            return (
                $alternatives !== []
                && array_all($alternatives, fn (Schema $branch) => $this->accepts($branch, $target))
            );
        }

        $alternatives = $target->alternatives();

        if ($alternatives !== []) {
            return array_any($alternatives, fn (Schema $branch) => $this->accepts($source, $branch));
        }

        $sourceTypes = $source->types();
        $targetTypes = $target->types();
        $acceptedTypes = in_array('number', $targetTypes, true) ? [...$targetTypes, 'integer'] : $targetTypes;

        if ($sourceTypes !== [] && $targetTypes !== [] && array_diff($sourceTypes, $acceptedTypes) !== []) {
            return false;
        }

        $targetValues = $target->enumValues();

        if ($targetValues !== null) {
            $sourceValues = $source->enumValues();

            if (
                $sourceValues === null
                || !array_all($sourceValues, $target->allowsEnumValue(...))
            ) {
                return false;
            }
        }

        if (
            ($sourceTypes === []
            || in_array('string', $sourceTypes, true))
            && !$this->stringsFit($source, $target)
        ) {
            return false;
        }

        if (in_array('array', $sourceTypes, true)) {
            return $this->accepts(
                $source->effectiveItems() ?? Schema::empty(),
                $target->effectiveItems() ?? Schema::empty(),
            );
        }

        return true;
    }

    private function stringsFit(Schema $source, Schema $target): bool
    {
        if (
            $this->checkFormats
            && $target->effectiveFormat() !== null
            && $source->effectiveFormat() !== $target->effectiveFormat()
            && $target->enumValues() === null
        ) {
            return false;
        }

        if (!$this->checkStringLengths) {
            return true;
        }

        $maximum = $target->effectiveMaxLength();
        $sourceMaximum = $source->effectiveMaxLength();

        return (
            ($source->effectiveMinLength() ?? 0) >= ($target->effectiveMinLength() ?? 0)
            && ($maximum === null || $sourceMaximum !== null && $sourceMaximum <= $maximum)
        );
    }
}
