<?php

namespace PHPinnacle\Langcat\Request\Students;

use InvalidArgumentException;
use LogicException;

final class AssignStudentToSchoolRequest
{
    private ?int $schoolId = null;

    public static function make(): self
    {
        return new self;
    }

    public function schoolId(int $schoolId): self
    {
        if ($schoolId < 1) {
            throw new InvalidArgumentException('School ID must be positive.');
        }

        $this->schoolId = $schoolId;

        return $this;
    }

    /** @return array{schoolId: int} */
    public function toArray(): array
    {
        return [
            'schoolId' => $this->schoolId ?? throw new LogicException('School ID is required.'),
        ];
    }
}
