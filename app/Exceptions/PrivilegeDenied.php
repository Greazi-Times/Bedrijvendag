<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;

/**
 * A role or account change refused by App\Support\Access. Rendered as a 403
 * like any authorization failure, and logged as security.privilege.denied
 * once the request has failed, so a rolled-back transaction can't swallow it.
 */
class PrivilegeDenied extends AuthorizationException
{
    public function __construct(string $message, public readonly string $severity = 'high')
    {
        parent::__construct($message);
    }
}
