<?php

namespace PHPinnacle\Ferry\Contracts;

use PHPinnacle\Ferry\Data\ChangeMessage;

interface ChangeConsumer
{
    /** @param list<string> $topics */
    public function subscribe(array $topics): void;

    public function consume(int $timeout): ?ChangeMessage;

    public function commit(ChangeMessage $message): void;

    /**
     * @param list<string> $topics
     *
     * @return array<string, int>
     */
    public function lag(array $topics): array;

    public function close(): void;
}
