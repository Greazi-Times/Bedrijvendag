<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;

/** A role's permissions as a module × action grid. State is a list of permission names. */
class PermissionMatrix extends Field
{
    protected string $view = 'filament.forms.components.permission-matrix';

    protected function setUp(): void
    {
        parent::setUp();

        $this->default([]);
        $this->rule('array');
    }
}
