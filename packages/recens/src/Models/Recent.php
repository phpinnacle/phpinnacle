<?php

namespace PHPinnacle\Recens\Models;

use Carbon\CarbonImmutable;
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
 * @property string $group
 * @property string $url
 * @property string $icon
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
        'group',
        'url',
        'icon',
        'title',
        'created_at',
    ];

    public static function list(?int $limit = null): array
    {
        $query = self::query()
            ->where('user_id', Auth::id())
            ->orderByDesc('created_at');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get()->all();
    }

    public static function record(array $data): void
    {
        $url = (string) Request::uri()->withQuery([], merge: false);
        $id = Uuid::uuid5(Uuid::NAMESPACE_URL, $url)->toString();

        self::query()
            ->updateOrCreate(
                [
                    'id' => $id,
                ],
                array_replace($data, [
                    'user_id' => Auth::id(),
                    'url' => $url,
                    'created_at' => now(),
                ]),
            );
    }

    public function getConnectionName(): ?string
    {
        return config('phpinnacle-recens.connection', parent::getConnectionName());
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getLabel(): string
    {
        return $this->title;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function prunable(): Builder
    {
        $days = (int) config('phpinnacle-recens.prune.days', 7);

        return static::query()->where('created_at', '<=', now()->subDays($days));
    }
}
