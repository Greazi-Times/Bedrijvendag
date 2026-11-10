<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;

class NewsletterSubscriptionController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email:rfc,dns', 'max:255'],
        ]);

        // Subscribing here is itself the explicit action; the shown text and version are recorded.
        NewsletterSubscriber::giveConsent($validated['email'], 'website', __('messages.newsletter_consent_text'));

        return response()->json(['ok' => true]);
    }

    /** A GET only shows the confirmation page, so mail scanners that open links cannot unsubscribe anyone. */
    public function showUnsubscribe(NewsletterSubscriber $subscriber)
    {
        return view('newsletter.unsubscribe', ['subscriber' => $subscriber, 'done' => false]);
    }

    public function unsubscribe(NewsletterSubscriber $subscriber)
    {
        $subscriber->unsubscribed_at ?? $subscriber->withdraw();

        return view('newsletter.unsubscribe', ['subscriber' => $subscriber, 'done' => true]);
    }
}
