<?php

namespace PHPinnacle\Langcat\Request\Students;

use InvalidArgumentException;
use LogicException;
use PHPinnacle\Langcat\Enum\StudentType;

final class CreateStudentRequest
{
    private ?string $firstName = null;

    private ?string $lastName = null;

    private ?int $schoolId = null;

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

    public function schoolId(int $schoolId): self
    {
        if ($schoolId < 1) {
            throw new InvalidArgumentException('School ID must be positive.');
        }

        $this->schoolId = $schoolId;

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

    /** @return array{firstName: string, lastName: string, schoolId: int, hasParentAccounts: bool, type: string} */
    public function toArray(): array
    {
        $type = $this->type ?? throw new LogicException('Student type is required.');

        return [
            'firstName' => $this->firstName ?? throw new LogicException('First name is required.'),
            'lastName' => $this->lastName ?? throw new LogicException('Last name is required.'),
            'schoolId' => $this->schoolId ?? throw new LogicException('School ID is required.'),
            'hasParentAccounts' => $this->hasParentAccounts ?? throw new LogicException(
                'Parent account preference is required.',
            ),
            'type' => $type->value,
        ];
    }

    private function nonEmpty(string $value, string $name): string
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException("{$name} cannot be empty.");
        }

        return $value;
    }
}
