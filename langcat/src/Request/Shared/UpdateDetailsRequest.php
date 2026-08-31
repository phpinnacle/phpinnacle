<?php

namespace PHPinnacle\Langcat\Request\Shared;

use DateTimeImmutable;
use InvalidArgumentException;
use LogicException;

final class UpdateDetailsRequest
{
    /** @var array<string, string> */
    private array $data = [];

    public static function make(): self
    {
        return new self;
    }

    public function phone(string $value): self
    {
        return $this->set('phone', $value);
    }

    public function mobile(string $value): self
    {
        return $this->set('mobile', $value);
    }

    public function email(string $value): self
    {
        return $this->setEmail('email', $value);
    }

    public function skype(string $value): self
    {
        return $this->set('skype', $value);
    }

    public function pesel(string $value): self
    {
        return $this->set('pesel', $value);
    }

    public function street(string $value): self
    {
        return $this->set('street', $value);
    }

    public function zipCode(string $value): self
    {
        return $this->set('zipCode', $value);
    }

    public function city(string $value): self
    {
        return $this->set('city', $value);
    }

    public function country(string $value): self
    {
        return $this->set('country', $value);
    }

    public function post(string $value): self
    {
        return $this->set('post', $value);
    }

    public function province(string $value): self
    {
        return $this->set('province', $value);
    }

    public function nipNumber(string $value): self
    {
        return $this->set('nipNumber', $value);
    }

    public function birthDate(string $value): self
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        if ($value !== '' && ($date === false || $date->format('Y-m-d') !== $value)) {
            throw new InvalidArgumentException('Birth date must use Y-m-d format.');
        }

        return $this->set('birthDate', $value);
    }

    public function birthPlace(string $value): self
    {
        return $this->set('birthPlace', $value);
    }

    public function description(string $value): self
    {
        return $this->set('description', $value);
    }

    public function billingName(string $value): self
    {
        return $this->set('billingName', $value);
    }

    public function billingStreet(string $value): self
    {
        return $this->set('billingStreet', $value);
    }

    public function billingCity(string $value): self
    {
        return $this->set('billingCity', $value);
    }

    public function billingZipCode(string $value): self
    {
        return $this->set('billingZipCode', $value);
    }

    public function billingNipNumber(string $value): self
    {
        return $this->set('billingNipNumber', $value);
    }

    public function billingPesel(string $value): self
    {
        return $this->set('billingPesel', $value);
    }

    public function billingEmail(string $value): self
    {
        return $this->setEmail('billingEmail', $value);
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        return $this->data !== []
            ? $this->data
            : throw new LogicException('At least one details field must be updated.');
    }

    private function set(string $key, string $value): self
    {
        $this->data[$key] = $value;

        return $this;
    }

    private function setEmail(string $key, string $value): self
    {
        if ($value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Email must be valid.');
        }

        return $this->set($key, $value);
    }
}
