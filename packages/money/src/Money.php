<?php

namespace PHPinnacle\Money;

use Illuminate\Database\Eloquent\Casts\Attribute;
use InvalidArgumentException;
use JsonSerializable;
use Livewire\Wireable;
use PHPinnacle\Money\Services\MoneyFormatter;
use PHPinnacle\Money\Services\MoneyParser;

readonly class Money implements JsonSerializable, Wireable
{
    public string $currency;

    public function __construct(
        public int $amount,
        string $currency = 'USD',
    ) {
        $this->currency = strtoupper(trim($currency));

        if (!Currencies::exists($this->currency)) {
            throw new InvalidArgumentException('Invalid currency.');
        }
    }

    public static function attribute(string $field = 'price', string $currency = 'currency'): Attribute
    {
        return Attribute::make(
            get: fn (?int $value, array $attributes) => $value !== null
                ? self::parse($value, $attributes[$currency])
                : null,
            set: function (self|array|int|null $value) use ($field, $currency) {
                $value = is_array($value) ? self::parse($value) : $value;

                return (
                    is_int($value) || $value === null
                        ? [
                            $field => $value,
                        ] : [
                            $field => $value->amount,
                            $currency => $value->currency,
                        ]
                );
            },
        );
    }

    public static function fromLivewire($value): self
    {
        return self::parse($value);
    }

    public static function parse(self|array|string|int|null $amount, ?string $currency = null): self
    {
        return MoneyParser::parse($amount, $currency);
    }

    public static function sum(self|array $first, self|array|null ...$collection): self
    {
        $first = self::parse($first);

        return $collection !== [] ? $first->add(...array_map(self::parse(...), $collection)) : $first;
    }

    public static function zero(string $currency = 'USD'): self
    {
        return new self(0, $currency);
    }

    public function add(self ...$others): self
    {
        $amount = $this->amount;

        foreach ($others as $other) {
            $this->guard($other);

            $amount += $other->amount;
        }

        return new self($amount, $this->currency);
    }

    public function allocate(array $ratios): array
    {
        $results = [];
        $fractions = [];
        $total = array_sum($ratios);
        $remainder = $this->amount;

        if ($total <= 0) {
            throw new InvalidArgumentException('Cannot allocate to none, sum of ratios must be greater than zero');
        }

        foreach ($ratios as $key => $ratio) {
            if ($ratio < 0) {
                throw new InvalidArgumentException('Cannot allocate to none, ratio must be zero or positive');
            }

            $share = ($this->amount * $ratio) / $total;
            $floor = (int) floor($share);
            $results[$key] = new self($floor, $this->currency);
            $fractions[$key] = $share - $floor;
            $remainder -= $floor;
        }

        arsort($fractions);

        foreach (array_keys($fractions) as $index) {
            if ($remainder <= 0) {
                break;
            }

            $results[$index] = new self($results[$index]->amount + 1, $results[$index]->currency);
            $remainder--;
        }

        return $results;
    }

    public function compare(self $other): int
    {
        $this->guard($other);

        return $this->amount <=> $other->amount;
    }

    public function decimal(): string
    {
        $valueBase = (string) $this->amount;

        $subunit = $this->subunit();
        $valueLength = strlen($valueBase);

        if ($valueLength > $subunit) {
            $formatted = substr($valueBase, 0, $valueLength - $subunit);
            $decimalDigits = substr($valueBase, $valueLength - $subunit);

            if (strlen($decimalDigits) > 0) {
                $formatted .= '.' . $decimalDigits;
            }
        } else {
            $formatted = '0.' . str_pad('', $subunit - $valueLength, '0') . $valueBase;
        }

        assert($formatted !== '', 'Formatted money value must not be empty.');

        return $formatted;
    }

    public function eq(self $other): bool
    {
        $this->guard($other);

        return $this->amount === $other->amount;
    }

    /**
     * @return array<self>
     */
    public function explode(): array
    {
        $exp = pow(10, $this->subunit());
        $coins = $this->amount % $exp;

        return [
            new self(($this->amount - $coins) / $exp, $this->currency),
            new self($coins, $this->currency),
        ];
    }

    public function format(?string $format = null, ?string $locale = null): string
    {
        return MoneyFormatter::format($this, $format, $locale);
    }

    public function gt(self $other, bool $equal = false): bool
    {
        $this->guard($other);

        return $equal
            ? $this->amount >= $other->amount
            : $this->amount > $other->amount;
    }

    public function isSome(): bool
    {
        return $this->amount !== 0;
    }

    public function isZero(): bool
    {
        return $this->amount === 0;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function lt(self $other, bool $equal = false): bool
    {
        $this->guard($other);

        return $equal
            ? $this->amount <= $other->amount
            : $this->amount < $other->amount;
    }

    public function max(Money ...$others): self
    {
        $amount = $this->amount;

        foreach ($others as $other) {
            $this->guard($other);

            $amount = max($amount, $other->amount);
        }

        return new self($amount, $this->currency);
    }

    public function min(Money ...$others): self
    {
        $amount = $this->amount;

        foreach ($others as $other) {
            $this->guard($other);

            $amount = min($amount, $other->amount);
        }

        return new self($amount, $this->currency);
    }

    public function mul(int $qty): self
    {
        return new self($this->amount * $qty, $this->currency);
    }

    public function percent(int|float $value): self
    {
        if ($value < 0 || $value > 100) {
            throw new InvalidArgumentException('Percentage must be between 0 and 100.');
        }

        return new self((int) floor(($this->amount * $value) / 100), $this->currency);
    }

    public function sub(self ...$others): self
    {
        $amount = $this->amount;

        foreach ($others as $other) {
            $this->guard($other);

            $amount -= $other->amount;
        }

        return new self($amount, $this->currency);
    }

    public function subunit(): int
    {
        return Currencies::subunit($this->currency);
    }

    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
        ];
    }

    public function toLivewire(): array
    {
        return [
            'amount' => $this->decimal(),
            'currency' => $this->currency,
        ];
    }

    public function zeroize(): self
    {
        return new self(0, $this->currency);
    }

    private function guard(self $other): void
    {
        if ($other->amount === 0) {
            return;
        }

        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Currencies do not match.');
        }
    }

    public function __toString(): string
    {
        return $this->decimal();
    }
}
