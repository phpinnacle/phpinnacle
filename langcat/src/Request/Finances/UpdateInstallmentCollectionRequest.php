<?php

namespace PHPinnacle\Langcat\Request\Finances;

use PHPinnacle\Langcat\Support\RequestValue;

final class UpdateInstallmentCollectionRequest
{
    /** @var array<string, string> */
    private array $data = [];

    public static function make(): self
    {
        return new self;
    }

    public function name(string $value): self
    {
        $this->data['name'] = RequestValue::nonEmpty($value, 'Collection name');

        return $this;
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        return $this->data;
    }
}
