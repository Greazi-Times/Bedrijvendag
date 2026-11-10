<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Form;
use App\Models\FormVersion;

/**
 * The one place that decides which form an edition uses: its own override when one is configured, otherwise the
 * global default of that kind. Nothing is copied into editions that inherit.
 */
class FormResolver
{
    public const COLUMNS = ['enrollment' => 'enrollment_form_id', 'info' => 'info_form_id'];

    public function __construct(private GlobalForms $globals) {}

    public function resolve(Event $event, string $kind): ?Form
    {
        return $this->override($event, $kind) ?? $this->globals->get($kind);
    }

    /** The published version new submissions use, or null while the effective form has never been published. */
    public function version(Event $event, string $kind): ?FormVersion
    {
        return $this->resolve($event, $kind)?->latestVersion;
    }

    /** The edition-specific form, only when it really belongs to this edition. A stray pointer is ignored, never trusted. */
    public function override(Event $event, string $kind): ?Form
    {
        $id = $event->{self::COLUMNS[$kind]};
        $form = $id ? Form::query()->find($id) : null;

        return $form && $form->isEditionSpecific() && $form->event_id === $event->id && $form->kind === $kind ? $form : null;
    }

    public function isCustomized(Event $event, string $kind): bool
    {
        return $this->override($event, $kind) !== null;
    }
}
