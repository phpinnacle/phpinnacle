<?php

namespace PHPinnacle\Cerber\Exceptions;

use Throwable;

class OAuthException extends CerberException
{
    public function __construct(
        public string $error,
        string $message,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, previous: $previous);
    }

    public function getError(): string
    {
        return __('phpinnacle-cerber::auth.errors.' . $this->error);
    }
}
