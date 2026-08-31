<?php

namespace PHPinnacle\Money\Forms;

use Closure;
use Filament\Forms\Components\Concerns\CanBeReadOnly;
use Filament\Forms\Components\Concerns\HasAffixes;
use Filament\Forms\Components\Concerns\HasExtraInputAttributes;
use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Concerns\HasStep;
use Filament\Forms\Components\Field;
use Filament\Support\Concerns\HasExtraAlpineAttributes;
use PHPinnacle\Money\Money;
use PHPinnacle\Money\Rules\MoneyRule;

class MoneyInput extends Field
{
    use CanBeReadOnly;
    use HasAffixes;
    use HasExtraAlpineAttributes;
    use HasExtraInputAttributes;
    use HasPlaceholder;
    use HasStep;

    protected string $view = 'phpinnacle-money::forms.money';

    protected Closure|Money|string|int|null $eqValue = null;

    protected Closure|Money|string|int|null $maxValue = null;

    protected Closure|Money|string|int|null $minValue = null;

    protected Closure|bool $nullable = false;

    protected array $currencies = [];

    protected string $defaultCurrency = 'USD';

    public function currencies(array $currencies, ?string $default = null): self
    {
        $this->currencies = $currencies;
        $this->defaultCurrency = $default ?? current($currencies) ?? 'USD';

        return $this;
    }

    public function equal(Closure|Money|string|int $value): static
    {
        $this->eqValue = $value;
        $this->minValue = $this->maxValue = null;

        return $this->rule(
            fn (self $component) => MoneyRule::eq($component->getEqValue()),
            fn (self $component) => filled($component->getEqValue()),
        );
    }

    public function getCurrencies(): array
    {
        return $this->currencies;
    }

    public function getEqValue(): Money|string|int|null
    {
        return $this->evaluate($this->eqValue);
    }

    public function getMaxValue(): Money|string|int|null
    {
        return $this->evaluate($this->maxValue);
    }

    public function getMinValue(): Money|string|int|null
    {
        return $this->evaluate($this->minValue);
    }

    public function greater(Closure|Money|string|int $value, bool $strict = false): static
    {
        $this->maxValue = $value;

        return $this->rule(
            fn (self $component) => $strict
                ? MoneyRule::lt($component->getMaxValue())
                : MoneyRule::lte($component->getMaxValue()),
            fn (self $component) => filled($component->getMaxValue()),
        );
    }

    public function lesser(Closure|Money|string|int $value, bool $strict = false): static
    {
        $this->minValue = $value;

        return $this->rule(
            fn (self $component) => $strict
                ? MoneyRule::gt($component->getMinValue())
                : MoneyRule::gte($component->getMinValue()),
            fn (self $component) => filled($component->getMinValue()),
        );
    }

    public function notEqual(Closure|Money|string|int $value): static
    {
        $this->eqValue = $value;

        return $this->rule(
            fn (self $component) => MoneyRule::neq($component->getEqValue()),
            fn (self $component) => filled($component->getEqValue()),
        );
    }

    public function nullable(Closure|bool $condition = true): static
    {
        $this->nullable = $condition;

        return parent::nullable($condition);
    }

    public function required(Closure|bool $condition = true): static
    {
        $this->greater(0, strict: true);

        return parent::required($condition);
    }

    public function setUp(): void
    {
        parent::setUp();

        $this
            ->prefixIcon('phosphor-money')
            ->formatStateUsing(fn (mixed $state) => Money::parse($state, $this->defaultCurrency)->toLivewire())
            ->dehydrateStateUsing(self::dehydrateHook(...))
            ->default(fn () => Money::zero($this->defaultCurrency));
    }

    public function table(): self
    {
        return $this->prefixIcon(null);
    }

    private function dehydrateHook(self $component, mixed $state): ?Money
    {
        $value = Money::parse($state, $component->defaultCurrency);

        return $component->evaluate($component->nullable) && $value->isZero() ? null : $value;
    }
}
