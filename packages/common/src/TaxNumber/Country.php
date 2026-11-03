<?php

namespace PHPinnacle\Common\TaxNumber;

interface Country
{
    public function code(): string;

    public function normalize(string $number): string;

    public function validate(string $number): bool;

    public function lookup(string $number): LookupResult;
}
