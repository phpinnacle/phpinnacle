<?php

namespace PHPinnacle\Canvio\Enum;

enum AccountUserSort: string
{
    case Email = 'email';
    case Id = 'id';
    case IntegrationId = 'integration_id';
    case LastLogin = 'last_login';
    case SisId = 'sis_id';
    case Username = 'username';
}
