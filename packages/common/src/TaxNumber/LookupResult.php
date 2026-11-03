<?php

namespace PHPinnacle\Common\TaxNumber;

final readonly class LookupResult
{
    private function __construct(
        public ?string $name,
        public ?string $status,
        public ?string $address,
        public ?LookupFailure $failure,
    ) {}

    public static function success(string $name, ?string $status = null, ?string $address = null): self
    {
        return new self($name, $status, $address, null);
    }

    public static function failure(LookupFailure $failure): self
    {
        return new self(null, null, null, $failure);
    }

    /** @return array{name: string, status: ?string, address: ?string}|null */
    public function data(): ?array
    {
        return (
            $this->name === null
                ? null
                : [
                    'name' => $this->name,
                    'status' => $this->status,
                    'address' => $this->address,
                ]
        );
    }
}
