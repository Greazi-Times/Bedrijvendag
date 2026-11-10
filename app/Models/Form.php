<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Support\Forms\FormSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Form extends Model
{
    use Audited;

    // system_key is deliberately not fillable: only GlobalForms creates the two global defaults.
    protected $fillable = ['name', 'kind', 'is_template', 'event_id', 'draft_schema'];

    protected $casts = ['is_template' => 'boolean', 'draft_schema' => 'array'];

    protected array $auditAttributes = ['name', 'kind', 'is_template', 'event_id'];

    public const KEY_ENROLLMENT = 'default_enrollment';

    public const KEY_INFO = 'default_info';

    public const GLOBAL_KEYS = ['enrollment' => self::KEY_ENROLLMENT, 'info' => self::KEY_INFO];

    protected static function booted(): void
    {
        static::deleting(function (self $form): void {
            throw_if($form->isGlobal(), \LogicException::class, 'The global default forms cannot be deleted.');
            throw_if($form->isInUse(), \LogicException::class, 'A form that companies have answered cannot be deleted; their answers would become unreadable.');
        });

        // Global and edition-specific are different things and never turn into one another.
        static::saving(function (self $form): void {
            $key = $form->system_key;

            if ($key !== null) {
                throw_unless(in_array($key, self::GLOBAL_KEYS, true) && self::GLOBAL_KEYS[$form->kind] === $key, \LogicException::class, 'Unknown global form key.');
                throw_if($form->event_id !== null || $form->is_template, \LogicException::class, 'A global form belongs to no edition and is not a template.');
            }

            if ($form->exists && $form->isDirty(['system_key', 'kind', 'event_id']) && ($form->getOriginal('system_key') !== null || $form->system_key !== null)) {
                throw new \LogicException('A form cannot become, or stop being, a global default.');
            }
        });
    }

    public function isGlobal(): bool
    {
        return $this->system_key !== null;
    }

    /** One edition's own copy, as opposed to a global default or a reusable template. */
    public function isEditionSpecific(): bool
    {
        return $this->system_key === null && $this->event_id !== null;
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(FormVersion::class)->orderByDesc('version');
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(FormVersion::class)->latestOfMany('version');
    }

    /**
     * Freezes the current draft as a new immutable version. Existing submissions keep
     * pointing at the version they were answered against.
     *
     * @throws ValidationException when the draft is not publishable
     */
    public function publish(?User $by = null): FormVersion
    {
        $problems = FormSchema::problems($this->draft_schema ?? [], $this->kind);

        if ($problems !== []) {
            throw ValidationException::withMessages(['draft_schema' => $problems]);
        }

        return DB::transaction(function () use ($by): FormVersion {
            $this->newQuery()->whereKey($this->getKey())->lockForUpdate()->first();
            $schema = $this->draft_schema;
            $last = $this->versions()->first();

            if ($last && self::canonical($last->schema) === self::canonical($schema)) {
                return $last; // nothing changed; do not burn a version number
            }

            return $this->versions()->create([
                'version' => ($last?->version ?? 0) + 1,
                'schema' => $schema,
                'published_by' => $by?->getKey(),
                'published_at' => now(),
            ]);
        });
    }

    /** Key-order-insensitive form for comparing schemas. */
    private static function canonical(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $value = array_map(self::canonical(...), $value);
        array_is_list($value) || ksort($value);

        return $value;
    }

    /** True once any enrollment or second-form submission was answered against one of this form's versions. */
    public function isInUse(): bool
    {
        $versions = $this->versions()->pluck('id');

        return $versions->isNotEmpty()
            && (Enrollment::query()->whereIn('form_version_id', $versions)->exists() || EnrollmentInfoSubmission::query()->whereIn('form_version_id', $versions)->exists());
    }

    /** Independent copy (own draft, no version history) for another edition or as a template. */
    public function duplicate(?Event $for = null, ?string $name = null, bool $asTemplate = false): self
    {
        $source = $this->latestVersion?->schema ?? $this->draft_schema;

        return self::create([
            'name' => $name ?? $this->name,
            'kind' => $this->kind,
            'is_template' => $asTemplate,
            'event_id' => $for?->getKey(),
            'draft_schema' => $source,
        ]);
    }
}
