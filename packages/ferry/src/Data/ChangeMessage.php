<?php

namespace PHPinnacle\Ferry\Data;

/**
 * @phpstan-type ChangeSource array{
 *     db: string,
 *     schema: string,
 *     table: string,
 *     ts_ms: int,
 *     lsn: int|null,
 *     txId: int|null,
 * }
 * @phpstan-type ChangeEnvelope array{
 *     op: 'c'|'u'|'d'|'r',
 *     before: array<string, mixed>|null,
 *     after: array<string, mixed>|null,
 *     source: ChangeSource,
 * }
 * @phpstan-type ChangeMetadata array{
 *     topic: string,
 *     partition: int,
 *     offset: int,
 *     timestamp: int,
 *     database: string,
 *     schema: string,
 *     table: string,
 *     lsn: int|null,
 *     txId: int|null,
 * }
 */
final readonly class ChangeMessage
{
    private const string UNAVAILABLE_VALUE = '__debezium_unavailable_value';

    public function __construct(
        public string $topic,
        public int $partition,
        public int $offset,
        public ?string $payload,
    ) {}

    public function id(): string
    {
        return sprintf('%s:%d:%d', $this->topic, $this->partition, $this->offset);
    }

    /** @return ChangeEnvelope|null */
    public function envelope(): ?array
    {
        if ($this->payload === null) {
            return null;
        }

        /** @var array{payload: ChangeEnvelope} $message */
        $message = json_decode($this->payload, true, flags: JSON_THROW_ON_ERROR);
        $envelope = $message['payload'];

        if ($envelope['after'] !== null) {
            $envelope['after'] = $this->withUnchangedValues($envelope['after'], $envelope['before']);
        }

        return $envelope;
    }

    /**
     * @param ChangeSource $source
     * @return ChangeMetadata
     */
    public function metadata(array $source): array
    {
        return [
            'topic' => $this->topic,
            'partition' => $this->partition,
            'offset' => $this->offset,
            'timestamp' => $source['ts_ms'],
            'database' => $source['db'],
            'schema' => $source['schema'],
            'table' => $source['table'],
            'lsn' => $source['lsn'],
            'txId' => $source['txId'],
        ];
    }

    /**
     * @param array<string, mixed> $after
     * @param array<string, mixed>|null $before
     * @return array<string, mixed>
     */
    private function withUnchangedValues(array $after, ?array $before): array
    {
        foreach ($after as $column => $value) {
            if ($value !== self::UNAVAILABLE_VALUE) {
                continue;
            }

            if ($before !== null && array_key_exists($column, $before)) {
                $after[$column] = $before[$column];
            } else {
                unset($after[$column]);
            }
        }

        return $after;
    }
}
