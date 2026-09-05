<?php

namespace PHPinnacle\Langcat\Request\Users;

use InvalidArgumentException;
use PHPinnacle\Langcat\Enum\UserRole;

final class SearchUsersRequest
{
    /** @var array<string, int|string> */
    private array $query = [];

    public static function make(): self
    {
        return new self;
    }

    public function page(int $page): self
    {
        return $this->positive('page', $page);
    }

    public function perPage(int $perPage): self
    {
        if ($perPage < 1 || $perPage > 100) {
            throw new InvalidArgumentException('Items per page must be between 1 and 100.');
        }

        $this->query['perPage'] = $perPage;

        return $this;
    }

    public function sortBy(string $sortBy): self
    {
        if (!in_array($sortBy, ['+id', '-id'], true)) {
            throw new InvalidArgumentException('User sort must be +id or -id.');
        }

        return $this->set('sortBy', $sortBy);
    }

    public function archived(bool $archived = true): self
    {
        return $this->set('isArchived', (int) $archived);
    }

    public function name(string $name): self
    {
        return $this->setNonEmpty('name', $name);
    }

    public function nameLike(string $name): self
    {
        return $this->setNonEmpty('name_like', $name);
    }

    public function lastName(string $lastName): self
    {
        return $this->setNonEmpty('lastName', $lastName);
    }

    public function lastNameLike(string $lastName): self
    {
        return $this->setNonEmpty('lastName_like', $lastName);
    }

    public function fullNameLike(string $fullName): self
    {
        return $this->setNonEmpty('fullName_like', $fullName);
    }

    public function email(string $email): self
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Email must be valid.');
        }

        return $this->set('email', $email);
    }

    public function emailLike(string $email): self
    {
        return $this->setNonEmpty('email_like', $email);
    }

    public function role(UserRole $role): self
    {
        return $this->set('role', $role->value);
    }

    /** @return array<string, int|string> */
    public function toQuery(): array
    {
        return $this->query;
    }

    private function positive(string $key, int $value): self
    {
        if ($value < 1) {
            throw new InvalidArgumentException(ucfirst($key) . ' must be positive.');
        }

        return $this->set($key, $value);
    }

    private function setNonEmpty(string $key, string $value): self
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException('Search value cannot be empty.');
        }

        return $this->set($key, $value);
    }

    private function set(string $key, int|string $value): self
    {
        $this->query[$key] = $value;

        return $this;
    }
}
