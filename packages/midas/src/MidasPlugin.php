<?php

namespace PHPinnacle\Midas;

use Filament\Contracts\Plugin;
use Filament\Panel;
use PHPinnacle\Midas\Contracts\PaymentPayer;
use PHPinnacle\Midas\Contracts\TransactionSource;
use PHPinnacle\Midas\Services\PayerRegistry;
use PHPinnacle\Midas\Services\SourceRegistry;

class MidasPlugin implements Plugin
{
    public function __construct(
        private readonly SourceRegistry $sources,
        private readonly PayerRegistry $payers,
    ) {}

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        // @mago-expect lint:inline-variable-return
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    public function getId(): string
    {
        return 'phpinnacle/midas';
    }

    public function sources(TransactionSource ...$sources): static
    {
        $this->sources->register(...$sources);

        return $this;
    }

    public function payers(PaymentPayer ...$payers): static
    {
        $this->payers->register(...$payers);

        return $this;
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            Resources\Methods\MethodResource::class,
            Resources\Plans\PlanResource::class,
            Resources\Transactions\TransactionResource::class,
        ]);
    }

    public function boot(Panel $panel): void {}
}
