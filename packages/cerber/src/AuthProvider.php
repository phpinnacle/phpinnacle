<?php

namespace PHPinnacle\Cerber;

use BackedEnum;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\FacebookProvider;
use Laravel\Socialite\Two\GithubProvider;
use Laravel\Socialite\Two\GoogleProvider;
use SocialiteProviders\Yandex\Provider as YandexProvider;

class AuthProvider implements HasColor, HasIcon, HasLabel
{
    public function __construct(
        private string $class,
        private string|Htmlable $label,
        private string|array|null $color = null,
        private string|BackedEnum|Htmlable|null $icon = null,
    ) {}

    public static function facebook(): self
    {
        return new self(
            class: FacebookProvider::class,
            label: __('phpinnacle-cerber::auth.providers.facebook'),
            color: Color::hex('#1877F2'),
            icon: 'cerber-facebook',
        );
    }

    public static function github(): self
    {
        return new self(
            class: GithubProvider::class,
            label: __('phpinnacle-cerber::auth.providers.github'),
            color: Color::hex('#2B3137'),
            icon: 'cerber-github',
        );
    }

    public static function google(): self
    {
        return new self(
            class: GoogleProvider::class,
            label: __('phpinnacle-cerber::auth.providers.google'),
            color: Color::hex('#4285F4'),
            icon: 'cerber-google',
        );
    }

    public static function make(string $class): self
    {
        return new self($class, str($class)->afterLast('\\')->apa());
    }

    public static function yandex(): self
    {
        return new self(
            class: YandexProvider::class,
            label: __('phpinnacle-cerber::auth.providers.yandex'),
            color: Color::hex('#FFCC00'),
            icon: 'cerber-yandex',
        );
    }

    public function color(string|array|null $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function driver(array $config): Provider
    {
        return Socialite::buildProvider($this->class, $config);
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getColor(): string|array|null
    {
        return $this->color;
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return $this->icon;
    }

    public function getLabel(): string|Htmlable
    {
        return $this->label;
    }

    public function icon(string|BackedEnum|Htmlable|null $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function label(string|Htmlable $label): self
    {
        $this->label = $label;

        return $this;
    }
}
