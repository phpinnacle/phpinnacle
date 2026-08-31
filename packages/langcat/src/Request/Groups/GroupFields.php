<?php

namespace PHPinnacle\Langcat\Request\Groups;

use InvalidArgumentException;
use PHPinnacle\Langcat\Enum\BillingCalculationBase;
use PHPinnacle\Langcat\Enum\BillingModel;
use PHPinnacle\Langcat\Enum\GradingType;
use PHPinnacle\Langcat\Enum\GroupType;
use PHPinnacle\Langcat\Support\RequestValue;

/** @internal */
trait GroupFields
{
    /** @var array<string, mixed> */
    private array $data = [];

    public function billingCalculationBase(BillingCalculationBase $base): static
    {
        return $this->set('billingCalculationBase', $base->value);
    }

    public function billingModel(BillingModel $model): static
    {
        return $this->set('billingModel', $model->value);
    }

    public function billingUnitPrice(string|int|float $price): static
    {
        if (is_string($price)) {
            if (!is_numeric($price) || (float) $price < 0) {
                throw new InvalidArgumentException('Billing unit price must be non-negative.');
            }
        } else {
            RequestValue::nonNegative($price, 'Billing unit price');
        }

        return $this->set('billingUnitPrice', $price);
    }

    public function companyId(?int $id): static
    {
        return $this->nullableId('companyId', $id);
    }

    public function companyPrice(int|float|null $price): static
    {
        return $this->set('companyPrice', $price === null ? null : RequestValue::nonNegative($price, 'Company price'));
    }

    public function documentTemplateId(?int $id): static
    {
        return $this->nullableId('documentTemplateId', $id);
    }

    public function endsOn(?string $date): static
    {
        return $this->set('endsOn', $date === null ? null : RequestValue::date($date, 'Group end date'));
    }

    public function gradingType(GradingType $type): static
    {
        return $this->set('gradingType', $type->value);
    }

    public function installmentCollectionId(?int $id): static
    {
        return $this->nullableId('installmentCollectionId', $id);
    }

    public function languageId(?int $id): static
    {
        return $this->nullableId('languageId', $id);
    }

    public function lessonDuration(int $minutes): static
    {
        return $this->set('lessonDuration', RequestValue::positive($minutes, 'Lesson duration'));
    }

    public function levelId(?int $id): static
    {
        return $this->nullableId('levelId', $id);
    }

    public function maxStudents(?int $number): static
    {
        return $this->set(
            'maxStudents',
            $number === null ? null : RequestValue::nonNegative($number, 'Maximum students'),
        );
    }

    public function minStudents(?int $number): static
    {
        return $this->set(
            'minStudents',
            $number === null ? null : RequestValue::nonNegative($number, 'Minimum students'),
        );
    }

    public function name(string $name): static
    {
        return $this->set('name', RequestValue::nonEmpty($name, 'Group name'));
    }

    public function numberOfLessons(int $number): static
    {
        return $this->set('numberOfLessons', RequestValue::positive($number, 'Number of lessons'));
    }

    public function planned(bool $planned = true): static
    {
        return $this->set('isPlanned', $planned);
    }

    public function programCollectionId(?int $id): static
    {
        return $this->nullableId('programCollectionId', $id);
    }

    public function startsOn(string $date): static
    {
        return $this->set('startsOn', RequestValue::date($date, 'Group start date'));
    }

    public function type(GroupType|int $type): static
    {
        return $this->set(
            'type',
            $type instanceof GroupType ? $type->value : RequestValue::positive($type, 'Group type ID'),
        );
    }

    private function nullableId(string $key, ?int $id): static
    {
        return $this->set($key, $id === null ? null : RequestValue::positive($id, 'Resource ID'));
    }

    private function set(string $key, mixed $value): static
    {
        $this->data[$key] = $value;

        return $this;
    }
}
