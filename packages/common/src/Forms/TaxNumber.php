<?php

namespace PHPinnacle\Common\Forms;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Support\Components\Attributes\Exposed;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Renderless;
use PHPinnacle\Common\TaxNumber\Country;
use PHPinnacle\Common\TaxNumber\Lookup;

class TaxNumber extends TextInput
{
    /** @var list<Country>|Closure */
    protected array|Closure $countries = [];

    protected string|Closure|null $country = null;

    protected string|Closure|null $cacheStore = null;

    protected int $cacheTtl = 3600;

    /** @param list<Country>|Closure $countries */
    public function countries(array|Closure $countries): static
    {
        $this->countries = $countries;

        return $this;
    }

    /** @return list<Country> */
    public function getCountries(): array
    {
        return $this->evaluate($this->countries);
    }

    protected function getCountrySource(): ?Country
    {
        $code = $this->getCountry();

        return array_find($this->getCountries(), fn ($country) => $country->code() === $code);
    }

    public function country(string|Closure|null $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function cache(string|Closure|null $store, int $ttl = 3600): static
    {
        $this->cacheStore = $store;
        $this->cacheTtl = $ttl;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->evaluate($this->country);
    }

    public function getCacheStore(): ?string
    {
        return $this->evaluate($this->cacheStore);
    }

    public function getCacheTtl(): int
    {
        return $this->cacheTtl;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->live(onBlur: true);
        $this->rule(fn (TaxNumber $component) => function (string $attribute, mixed $value, Closure $fail) use (
            $component,
        ) {
            $country = $component->getCountry();

            if ($country === null) {
                return;
            }

            $source = $component->getCountrySource();

            if (!is_string($value) || $source === null || !$source->validate($source->normalize($value))) {
                $fail('phpinnacle-common::tax-number.invalid_format')->translate(['country' => $country]);
            }
        });
        $this->suffixAction(
            Action::make('taxNumberInfo')
                ->label(__('phpinnacle-common::tax-number.information'))
                ->icon(Heroicon::OutlinedInformationCircle)
                ->iconButton()
                ->disabled(
                    fn (TaxNumber $component) => $component->isDisabled() || $component->getBlockerMessage() !== null,
                )
                ->tooltip(fn (TaxNumber $component) => $component->getBlockerMessage())
                ->view('phpinnacle-common::components.tax-number-info'),
        );
    }

    protected function getBlockerMessage(): ?string
    {
        $country = $this->getCountry();

        if ($country === null) {
            return __('phpinnacle-common::tax-number.no_country');
        }

        $source = $this->getCountrySource();

        if ($source === null) {
            return __('phpinnacle-common::tax-number.no_source');
        }

        $state = $this->getState();

        if (!is_string($state) || !$source->validate($source->normalize($state))) {
            return __('phpinnacle-common::tax-number.enter_valid_number');
        }

        return null;
    }

    /** @return array{message: ?string, lookup: ?array{name: string, status: ?string, address: ?string}} */
    #[Exposed]
    #[Renderless]
    public function lookupForJs(): array
    {
        if ($this->isDisabled() || $this->isHidden()) {
            return ['message' => null, 'lookup' => null];
        }

        $blocker = $this->getBlockerMessage();

        if ($blocker !== null) {
            return ['message' => $blocker, 'lookup' => null];
        }

        $cacheStore = $this->getCacheStore();
        $lookup = new Lookup(
            $cacheStore === null ? null : Cache::store($cacheStore),
            $this->getCacheTtl(),
        );
        $result = $lookup->resolve($this->getCountrySource(), $this->getState());

        return [
            'message' => $result->failure === null
                ? null
                : __('phpinnacle-common::tax-number.' . $result->failure->value),
            'lookup' => $result->data(),
        ];
    }
}
