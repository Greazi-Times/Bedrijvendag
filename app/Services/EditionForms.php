<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Form;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Moving an edition between "inherits the global default" and "has its own form". */
class EditionForms
{
    public function __construct(private FormResolver $resolver) {}

    /**
     * Gives the edition an independent, immediately published copy of a form (default: what it currently uses).
     * The copy is published as it stands, so submissions keep working without interruption.
     */
    public function customize(Event $event, string $kind, ?Form $source = null, ?User $by = null): Form
    {
        if ($this->resolver->isCustomized($event, $kind)) {
            throw ValidationException::withMessages(['form' => 'This edition already has its own form.']);
        }

        $source ??= $this->resolver->resolve($event, $kind);
        $schema = $source?->latestVersion?->schema ?? $source?->draft_schema;

        if (! $source || ! $schema || $source->kind !== $kind) {
            throw ValidationException::withMessages(['form' => 'There is no published form of this kind to copy.']);
        }

        return DB::transaction(function () use ($event, $kind, $schema, $by): Form {
            $form = Form::create([
                'name' => $event->name.' – '.($kind === 'enrollment' ? 'Enrollment (custom)' : 'Information (custom)'),
                'kind' => $kind, 'event_id' => $event->id, 'is_template' => false, 'draft_schema' => $schema,
            ]);
            $form->publish($by);
            $event->forceFill([FormResolver::COLUMNS[$kind] => $form->id])->save();

            return $form;
        });
    }

    /** The edition inherits the global default again. The custom form, its versions and every answer stay untouched. */
    public function revert(Event $event, string $kind): void
    {
        $event->forceFill([FormResolver::COLUMNS[$kind] => null])->save();
    }
}
