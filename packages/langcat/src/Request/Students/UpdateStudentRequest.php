<?php

namespace PHPinnacle\Langcat\Request\Students;

use InvalidArgumentException;
use LogicException;
use PHPinnacle\Langcat\Enum\StudentType;

final class UpdateStudentRequest
{
    private ?string $firstName = null;

    private ?string $lastName = null;

    private ?bool $hasParentAccounts = null;

    private ?StudentType $type = null;

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

    public function hasParentAccounts(bool $hasParentAccounts = true): self
    {
        $this->hasParentAccounts = $hasParentAccounts;

        return $this;
    }

    public function type(StudentType $type): self
    {
        $this->type = $type;

        return $this;
    }

    /** @return array<string, bool|string> */
    public function toArray(): array
    {
        $data = array_filter(
            [
                'firstName' => $this->firstName,
                'lastName' => $this->lastName,
                'hasParentAccounts' => $this->hasParentAccounts,
                'type' => $this->type?->value,
            ],
            static fn (mixed $value) => $value !== null,
        );

        return $data !== []
            ? $data
            : throw new LogicException('At least one student field must be updated.');
    }

    private function nonEmpty(string $value, string $name): string
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException("{$name} cannot be empty.");
        }

        return $value;
    }
}
