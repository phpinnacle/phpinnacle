<?php

namespace PHPinnacle\Langcat\Request\Groups;

use LogicException;
use PHPinnacle\Langcat\Enum\BillingModel;
use PHPinnacle\Langcat\Support\RequestValue;

final class CreateGroupRequest
{
    use GroupFields;

    public static function make(): self
    {
        return new self;
    }

    public function schoolId(int $schoolId): self
    {
        $this->data['schoolId'] = RequestValue::positive($schoolId, 'School ID');

        return $this;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        foreach ([
            'name',
            'schoolId',
            'startsOn',
            'numberOfLessons',
            'lessonDuration',
            'type',
            'billingModel',
            'gradingType',
        ] as $field) {
            if (!array_key_exists($field, $this->data)) {
                throw new LogicException("Group field [{$field}] is required.");
            }
        }

        if (
            in_array($this->data['billingModel'], [BillingModel::Hour->value, BillingModel::HourInAdvance->value], true)
            && (($this->data['billingCalculationBase'] ?? null) === null
            || ($this->data['billingUnitPrice'] ?? null) === null)
        ) {
            throw new LogicException('Hourly billing requires a calculation base and unit price.');
        }

        return $this->data;
    }
}
