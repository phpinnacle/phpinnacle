<?php

namespace PHPinnacle\Cerber\Models;

use Carbon\CarbonImmutable;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Uri;
use PHPinnacle\Cerber\Contracts\SocialAccountContext;

/**
 * @property string $id
 * @property string $type
 * @property string $name
 * @property array<string, mixed> $config
 * @property string $context
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
        'context',
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
    public static function valid(SocialAccountContext $context): Collection
    {
        return self::active()
            ->where('context', $context->name())
            ->get()
            ->filter(fn (self $provider) => $provider->isConfigValid());
    }

    public static function booted(): void
    {
        self::creating(function (self $record) {
            reset_sort($record);
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

    public function isConfigValid(): bool
    {
        return filled($this->config['client_id'] ?? null) && filled($this->config['client_secret'] ?? null);
    }

    public function isAvailableTo(SocialAccountContext $context): bool
    {
        return $this->is_active && $this->context === $context->name() && $this->isConfigValid();
    }

    public function frontendCallbackUrl(): string
    {
        return (string) Uri::of(config('phpinnacle-cerber.api.callback_url'))
            ->withQuery(['context' => $this->context, 'provider' => $this->id]);
    }

    /**
     * @return HasMany<SocialAccount, $this>
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }
}
