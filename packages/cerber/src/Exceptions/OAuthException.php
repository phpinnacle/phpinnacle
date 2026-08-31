<?php

namespace PHPinnacle\Cerber\Exceptions;

class OAuthException extends CerberException
{
    public function __construct(
        public string $error,
        string $message,
    ) {
        parent::__construct($message);
    }

    public function getError(): string
    {
        return __('phpinnacle-cerber::auth.errors.' . $this->error);
    }
}
