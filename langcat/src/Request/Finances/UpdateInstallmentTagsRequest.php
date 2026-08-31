<?php

namespace PHPinnacle\Langcat\Request\Finances;

use PHPinnacle\Langcat\Support\RequestValue;

final class UpdateInstallmentTagsRequest
{
    /** @var list<int> */
    private array $tagIds = [];

    public static function make(): self
    {
        return new self;
    }

    public function tag(int $id): self
    {
        $this->tagIds[] = RequestValue::positive($id, 'Tag ID');

        return $this;
    }

    /** @return array{tagIds: list<int>} */
    public function toArray(): array
    {
        return ['tagIds' => $this->tagIds];
    }
}
