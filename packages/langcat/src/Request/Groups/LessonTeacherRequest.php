<?php

namespace PHPinnacle\Langcat\Request\Groups;

use LogicException;
use PHPinnacle\Langcat\Support\RequestValue;

final class LessonTeacherRequest
{
    private ?int $id = null;

    private int|float|null $salary = null;

    public static function make(): self
    {
        return new self;
    }

    public function id(int $id): self
    {
        $this->id = RequestValue::positive($id, 'Teacher ID');

        return $this;
    }

    public function salary(int|float $salary): self
    {
        $this->salary = RequestValue::nonNegative($salary, 'Teacher salary');

        return $this;
    }

    /** @return array{id: int, salary?: int|float} */
    public function toArray(): array
    {
        $data = ['id' => $this->id ?? throw new LogicException('Teacher ID is required.')];

        if ($this->salary !== null) {
            $data['salary'] = $this->salary;
        }

        return $data;
    }
}
