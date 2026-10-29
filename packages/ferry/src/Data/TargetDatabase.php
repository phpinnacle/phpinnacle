<?php

namespace PHPinnacle\Ferry\Data;

use Illuminate\Support\Facades\Config;
use SensitiveParameter;

final readonly class TargetDatabase
{
    public function __construct(
        public string $host,
        public int $port,
        public string $database,
        public string $username,
        #[SensitiveParameter]
        public string $password,
    ) {}

    public static function fromConnection(string $name, ?string $targetHost = null): self
    {
        /** @var int|numeric-string $port */
        $port = Config::get(sprintf('database.connections.%s.port', $name), 5432);

        return new self(
            host: $targetHost !== null && $targetHost !== ''
                ? $targetHost
                : Config::string(sprintf('database.connections.%s.host', $name)),
            port: (int) $port,
            database: Config::string(sprintf('database.connections.%s.database', $name)),
            username: Config::string(sprintf('database.connections.%s.username', $name), ''),
            password: Config::string(sprintf('database.connections.%s.password', $name), ''),
        );
    }

    public function jdbcUrl(): string
    {
        return sprintf('jdbc:postgresql://%s:%d/%s', $this->host, $this->port, $this->database);
    }
}
