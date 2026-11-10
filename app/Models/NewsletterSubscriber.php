<?php

namespace App\Models;

use App\Casts\EncryptedEmail;
use App\Models\Concerns\Audited;
use App\Models\Concerns\HasEmailHash;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

/**
 * Optional promotional email. Independent of every event registration: an event address never ends up here unless the
 * person ticked the unchecked consent box, and deleting event data never deletes a subscription.
 */
class NewsletterSubscriber extends Model
{
    use Audited, HasEmailHash;

    protected array $emailHashes = ['email' => 'email_hash'];

    /** Sign-ups are not logged, only their deletion, without the email address. */
    protected static $recordEvents = ['deleted'];

    protected array $auditAttributes = ['subscribed_at', 'unsubscribed_at'];

    protected $casts = ['email' => EncryptedEmail::class, 'subscribed_at' => 'datetime', 'unsubscribed_at' => 'datetime'];

    protected $fillable = ['email', 'subscribed_at', 'consent_version', 'consent_text', 'consent_source', 'unsubscribed_at'];

    /** Only these may receive promotional mail. */
    public function scopeSubscribed(Builder $query): Builder
    {
        return $query->whereNull('unsubscribed_at')->whereNotNull('subscribed_at');
    }

    /**
     * Records consent for an address.
     *  - A subscription that is already active keeps its original consent record untouched (a second sign-up never
     *    overwrites the proof of the first). A legacy row without any record only gets the record filled in.
     *  - A withdrawn subscription is reactivated, with a new consent record, only through this explicit call: it is made
     *    by an affirmative action (ticking the box or using the newsletter form), never as a side effect.
     */
    public static function giveConsent(string $email, string $source, ?string $text = null): self
    {
        $subscriber = static::query()->whereEmail('email', $email)->first() ?? new static(['email' => $email]);
        $record = [
            'consent_version' => config('privacy.marketing_consent.version'),
            'consent_text' => $text ?? __('messages.marketing_consent_text'),
            'consent_source' => $source,
        ];

        if ($subscriber->exists && $subscriber->unsubscribed_at === null && $subscriber->subscribed_at !== null) {
            $subscriber->consent_version === null && $subscriber->fill($record)->save();

            return $subscriber;
        }

        $subscriber->fill($record + ['subscribed_at' => now(), 'unsubscribed_at' => null])->save();

        return $subscriber;
    }

    /** Withdraws consent. The row is kept (without anything else) so the address is not mailed again by mistake. */
    public function withdraw(): void
    {
        $this->forceFill(['unsubscribed_at' => now()])->save();
    }

    /** The link every promotional email must contain (signed, so it cannot be guessed or forged). */
    public function unsubscribeUrl(): string
    {
        return URL::signedRoute('newsletter.unsubscribe', ['subscriber' => $this->getKey()]);
    }
}
