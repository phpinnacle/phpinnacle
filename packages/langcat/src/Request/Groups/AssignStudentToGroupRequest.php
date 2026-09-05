<?php

namespace PHPinnacle\Langcat\Request\Groups;

use LogicException;
use PHPinnacle\Langcat\Enum\AssignType;
use PHPinnacle\Langcat\Support\RequestValue;

final class AssignStudentToGroupRequest
{
    private ?int $studentId = null;

    private ?AssignType $assignType = null;

    private ?string $enrolledDate = null;

    public static function make(): self
    {
        return new self;
    }

    public function studentId(int $id): self
    {
        $this->studentId = RequestValue::positive($id, 'Student ID');

        return $this;
    }

    public function assignType(AssignType $type): self
    {
        $this->assignType = $type;

        return $this;
    }

    public function enrolledDate(string $date): self
    {
        $this->enrolledDate = RequestValue::date($date, 'Enrollment date', false);

        return $this;
    }

    /** @return array<string, int|string> */
    public function toArray(): array
    {
        return array_filter(
            [
                'id' => $this->studentId ?? throw new LogicException('Student ID is required.'),
                'assignType' => ($this->assignType ?? throw new LogicException('Assignment type is required.'))->value,
                'enrolledDate' => $this->enrolledDate,
            ],
            static fn (mixed $value) => $value !== null,
        );
    }
}
