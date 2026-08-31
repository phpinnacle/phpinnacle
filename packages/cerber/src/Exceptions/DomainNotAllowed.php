<?php

namespace PHPinnacle\Cerber\Exceptions;

class DomainNotAllowed extends OAuthException
{
    public function __construct(string $email)
    {
        parent::__construct('domain_not_allowed', sprintf('Email %s is not allowed.', $email));
    }
}
