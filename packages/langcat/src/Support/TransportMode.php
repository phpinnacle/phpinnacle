<?php

namespace PHPinnacle\Langcat\Support;

/** @internal */
enum TransportMode
{
    case Json;
    case AnonymousForm;
    case EmptyResponse;
}
