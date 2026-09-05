<?php

namespace PHPinnacle\Cerber\Models;

use Filament\Facades\Filament;

class Login
{
    /**
     * @param array<int, string> $panels
     */
    public function __construct(
        public string $username,
        #[\SensitiveParameter]
        public string $password,
        public array $panels = [],
        public bool $verified = false,
    ) {}

    /**
     * @param array<int, string> $panels
     */
    public static function email(
        string $value,
        array $panels = [],
        #[\SensitiveParameter]
        ?string $password = null,
    ): self {
        $panels[] = Filament::getDefaultPanel()->getId();

        return new self($value, $password ?? Password::generate(), array_unique($panels));
    }

    public function verified(): self
    {
        return new self($this->username, $this->password, $this->panels, verified: true);
    }
}
