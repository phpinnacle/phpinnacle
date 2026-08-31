<?php

namespace PHPinnacle\Cerber\Models;

use Filament\Facades\Filament;

class Login
{
    public function __construct(
        public string $username,
        public string $password,
        public array $panels = [],
        public bool $verified = false,
    ) {}

    public static function email(string $value, array $panels = [], ?string $password = null): self
    {
        $panels[] = Filament::getDefaultPanel()->getId();

        return new self($value, $password ?? Password::generate(), array_unique($panels));
    }

    public function verified(): self
    {
        return new self($this->username, $this->password, $this->panels, verified: true);
    }
}
