<?php

namespace PHPinnacle\Ferry\Services;

use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Eloquent\Model;
use PHPinnacle\Ferry\Data\ChangeMessage;
use PHPinnacle\Ferry\Data\TrackedTable;
use PHPinnacle\Ferry\Enums\ChangeOperation;
use PHPinnacle\Ferry\Events\RecordChanged;
use PHPinnacle\Ferry\Events\RecordCreated;
use PHPinnacle\Ferry\Events\RecordDeleted;
use PHPinnacle\Ferry\Events\RecordUpdated;
use PHPinnacle\Ferry\Origins\Kafka\SourceFactory;

#[Singleton]
class TrackedTableRegistry
{
    /** @var array<class-string<Model>, TrackedTable> */
    private array $items = [];

    public function __construct(
        private readonly SourceFactory $sources,
    ) {}

    public function register(TrackedTable ...$tables): void
    {
        foreach ($tables as $table) {
            $this->items[$table->model] = $table;
        }
    }

    /** @return array<class-string<Model>, TrackedTable> */
    public function all(): array
    {
        return $this->items;
    }

    /** @return array<string, list<TrackedTable>> */
    public function byConnection(): array
    {
        $grouped = [];

        foreach ($this->items as $table) {
            $grouped[$table->connection][] = $table;
        }

        return $grouped;
    }

    /** @return list<string> */
    public function topics(): array
    {
        return array_values(array_unique(array_map($this->sources->applicationTopic(...), $this->items)));
    }

    /** @param array<string, mixed> $attributes */
    public function forTopic(string $topic, array $attributes): ?TrackedTable
    {
        $fallback = null;

        foreach ($this->items as $table) {
            if ($this->sources->applicationTopic($table) !== $topic) {
                continue;
            }

            if ($table->discriminators === []) {
                $fallback ??= $table;
            } elseif ($table->matches($attributes)) {
                return $table;
            }
        }

        return $fallback;
    }

    public function change(ChangeMessage $message): ?RecordChanged
    {
        $envelope = $message->envelope();

        if ($envelope === null) {
            return null;
        }

        $operation = ChangeOperation::fromDebezium($envelope['op']);
        $before = $envelope['before'];
        $after = $envelope['after'];

        if ($operation === null || $operation === ChangeOperation::Update && $before === $after) {
            return null;
        }

        /** @var array<string, mixed> $attributes */
        $attributes = $after ?? $before;
        $table = $this->forTopic($message->topic, $attributes);

        if ($table === null) {
            return null;
        }

        /** @var string|int $key */
        $key = $attributes[$table->primaryKey];
        $event = match ($operation) {
            ChangeOperation::Create => RecordCreated::class,
            ChangeOperation::Update => RecordUpdated::class,
            ChangeOperation::Delete => RecordDeleted::class,
        };

        return new $event(
            eventId: $message->id(),
            model: $table->model,
            key: $key,
            before: $before,
            after: $after,
            metadata: $message->metadata($envelope['source']),
        );
    }
}
