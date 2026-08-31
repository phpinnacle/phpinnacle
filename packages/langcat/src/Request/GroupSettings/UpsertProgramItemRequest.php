<?php

namespace PHPinnacle\Langcat\Request\GroupSettings;

use LogicException;
use PHPinnacle\Langcat\Support\RequestValue;

final class UpsertProgramItemRequest
{
    /** @var array<string, mixed> */
    private array $data = [];

    public static function make(): self
    {
        return new self;
    }

    public function element(int $lessonDetailsId, string $description): self
    {
        $this->data['elements'][] = [
            'lessonDetailsId' => RequestValue::positive($lessonDetailsId, 'Lesson details ID'),
            'description' => RequestValue::nonEmpty($description, 'Program item description'),
        ];

        return $this;
    }

    public function position(int $value): self
    {
        $this->data['position'] = RequestValue::nonNegativeInt($value, 'Program item position');

        return $this;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        if (!isset($this->data['elements'])) {
            throw new LogicException('At least one program item element is required.');
        }

        return $this->data;
    }
}
