<?php

namespace PHPinnacle\Langcat\Request\Students;

use InvalidArgumentException;
use LogicException;

final class UpdateParentRequest
{
    private ?string $firstName = null;

    private ?string $lastName = null;

    public static function make(): self
    {
        return new self;
    }

    public function firstName(string $firstName): self
    {
        $this->firstName = $this->nonEmpty($firstName, 'First name');

        return $this;
    }

    public function lastName(string $lastName): self
    {
        $this->lastName = $this->nonEmpty($lastName, 'Last name');

        return $this;
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        $data = array_filter(
            [
                'firstName' => $this->firstName,
                'lastName' => $this->lastName,
            ],
            static fn (?string $value) => $value !== null,
        );

        return $data !== []
            ? $data
            : throw new LogicException('At least one parent field must be updated.');
    }

    private function nonEmpty(string $value, string $name): string
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException("{$name} cannot be empty.");
        }

        return $value;
    }
}
