<?php

namespace PHPinnacle\Money\Livewire;

use Livewire\Mechanisms\HandleComponents\Synthesizers\Synth;
use PHPinnacle\Money\Money;

class MoneySynth extends Synth
{
    public static string $key = 'money';

    public static function match(mixed $target): bool
    {
        return $target instanceof Money;
    }

    public function dehydrate(Money $target): array
    {
        return [$target->toLivewire(), []];
    }

    public function hydrate(mixed $value): Money
    {
        return Money::parse($value);
    }

    public function get(&$target, $key): ?string
    {
        return match ($key) {
            'currency' => $target->currency,
            'amount' => $target->decimal(),
            default => null,
        };
    }

    public function set(&$target, $key, $value): void
    {
        $target = match ($key) {
            'currency' => new Money($target->amount, $value),
            'amount' => Money::parse((string) $value, $target->currency),
            default => null,
        };
    }
}
