<?php

namespace PHPinnacle\Recens\Models;

use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Ramsey\Uuid\Uuid;

/**
 * @property string $id
 * @property string $user_id
 * @property string|null $panel_id
 * @property string|int|null $tenant_id
 * @property CarbonImmutable|null $pinned_at
 * @property string|null $group
 * @property string $url
 * @property string|null $icon
 * @property string $title
 * @property CarbonImmutable $created_at
 */
class Recent extends Model implements HasIcon, HasLabel
{
    use HasUuids;
    use MassPrunable;

    public $timestamps = false;

    protected $table = 'recent';

    protected $fillable = [
        'id',
        'user_id',
        'panel_id',
        'tenant_id',
        'group',
        'url',
        'icon',
        'title',
        'created_at',
    ];

    /**
     * @param array<string, mixed> $data
     */
    public static function record(array $data): void
    {
        $context = self::context();

        if ($context['user_id'] === null) {
            return;
        }

        $url = (string) Request::uri()->withQuery([], merge: false);
        $tenant = $context['tenant_id'] ?? null;
        $id = Uuid::uuid5(Uuid::NAMESPACE_URL, serialize([
            'user' => (string) $context['user_id'],
            'panel' => $context['panel_id'],
            'tenant' => $tenant !== null ? (string) $tenant : null,
            'url' => $url,
        ]))->toString();

        self::query()
            ->where($context)
            ->updateOrCreate(
                ['id' => $id],
                array_replace($data, $context, [
                    'id' => $id,
                    'url' => $url,
                    'created_at' => now(),
                ]),
            );
    }

    /**
     * @return list<self>
     */
    public static function list(?int $limit = null): array
    {
        $pinned = self::forCurrentContext()
            ->whereNotNull('pinned_at')
            ->orderBy('pinned_at')
            ->orderBy('id')
            ->get();

        $query = self::forCurrentContext()->whereNull('pinned_at')->orderByDesc('created_at');

        if ($limit) {
            $query->limit($limit);
        }

        return array_values($pinned->concat($query->get())->all());
    }

    /** @return Builder<static> */
    public static function forUser(string|int|null $userId): Builder
    {
        return static::query()->where('user_id', $userId);
    }

    /** @return Builder<static> */
    public static function forCurrentContext(): Builder
    {
        $context = self::context();
        $userId = $context['user_id'];
        unset($context['user_id']);

        return static::forUser($userId)->where($context);
    }

    public function togglePin(): void
    {
        if ($this->pinned_at === null) {
            $this->pin();
        } else {
            $this->unpin();
        }
    }

    public function pin(): void
    {
        if ($this->pinned_at !== null) {
            return;
        }

        $this->pinned_at = CarbonImmutable::now();
        $this->save();
    }

    public function unpin(): void
    {
        $this->pinned_at = null;
        $this->save();
    }

    public static function clearHistory(): void
    {
        self::forCurrentContext()->whereNull('pinned_at')->delete();
    }

    /**
     * @return array{user_id: string|int|null, panel_id: string|null, tenant_id?: string|int|null}
     */
    private static function context(): array
    {
        $panel = Filament::getCurrentPanel();
        $context = [
            'user_id' => $panel !== null ? $panel->auth()->id() : Auth::id(),
            'panel_id' => $panel?->getId(),
        ];

        if (config('phpinnacle-recens.tenancy.model') !== null) {
            $context['tenant_id'] = Filament::getTenant()?->getKey() ?? config('phpinnacle-recens.tenancy.default');
        }

        return $context;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'pinned_at' => 'immutable_datetime',
        ];
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getLabel(): string
    {
        return $this->title;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    /**
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        $days = (int) config('phpinnacle-recens.prune.days', 7);

        return static::query()
            ->whereNull('pinned_at')
            ->where('created_at', '<=', now()->subDays($days))
            ->when(!config('phpinnacle-recens.prune.enabled', true), fn (Builder $query) => $query->whereRaw('1 = 0'));
    }

    public function getConnectionName(): ?string
    {
        return config('phpinnacle-recens.connection', parent::getConnectionName());
    }
}
