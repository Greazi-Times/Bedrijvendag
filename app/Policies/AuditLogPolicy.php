<?php

namespace App\Policies;

class AuditLogPolicy extends ModelPolicy
{
    protected string $module = 'audit-logs';
}
