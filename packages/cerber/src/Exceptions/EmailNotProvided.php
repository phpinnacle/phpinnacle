<?php

namespace PHPinnacle\Cerber\Exceptions;

class EmailNotProvided extends OAuthException
{
    public function __construct(string $provider)
    {
        parent::__construct('email_not_provided', sprintf('Provider %s does not return email', $provider));
    }
}
