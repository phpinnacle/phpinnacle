<?php

namespace PHPinnacle\Cerber\Models;

use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property string $id
 * @property string $type
 * @property string $name
 * @property array<string, mixed> $config
 * @property bool $is_active
 * @property int $sort
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read Collection<int, SocialAccount> $accounts
 */
class Provider extends Model implements HasLabel
{
    use HasUuids;

    public $timestamps = true;

    protected $table = 'oauth_providers';

    protected $attributes = [
        'is_active' => false,
    ];

    protected $casts = [
        'config' => 'encrypted:array',
        'is_active' => 'bool',
    ];

    protected $fillable = [
        'name',
        'type',
        'config',
        'is_active',
        'sort',
    ];

    /**
     * @return Builder<self>
     */
    public static function active(): Builder
    {
        return self::query()
            ->where('is_active', true)
            ->orderBy('sort');
    }

    /**
     * @return Collection<int, self>
     */
    public static function valid(): Collection
    {
        return self::active()
            ->get()
            ->filter(fn (self $provider) => $provider->isConfigValid());
    }

    /**
     * @return Collection<string, string>
     */
    public static function list(): Collection
    {
        return self::active()->pluck('name', 'type');
    }

    public static function get(string $type): self
    {
        return self::active()->where('type', $type)->firstOrFail();
    }

    public static function find(string $type): ?self
    {
        return self::active()->where('type', $type)->first();
    }

    public static function booted(): void
    {
        self::creating(function (self $record) {
            reset_sort($record);

            $config = $record->config;
            $config['redirect'] ??= $record->callbackUrl();

            $record->config = $config;
        });
    }

    public function getLabel(): string
    {
        return $this->name;
    }

    public function toggleActive(): void
    {
        $this->is_active = !$this->is_active;
        $this->save();
    }

    public function redirectUrl(?Panel $panel = null): string
    {
        $panel ??= Filament::getCurrentOrDefaultPanel();

        return $panel->route('auth.redirect', ['provider' => $this->getKey()]);
    }

    public function callbackUrl(?Panel $panel = null): string
    {
        $panel ??= Filament::getCurrentOrDefaultPanel();

        return $panel->route('auth.callback', ['provider' => $this->getKey()]);
    }

    public function isConfigValid(): bool
    {
        return filled($this->config['client_id'] ?? null) && filled($this->config['client_secret'] ?? null);
    }

    /**
     * @return HasMany<SocialAccount, $this>
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }
}
