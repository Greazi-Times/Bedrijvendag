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

        NewsletterSubscriber::updateOrCreate(
            ['email' => $validated['email']],
            ['subscribed_at' => now()]
        );

        return response()->json(['ok' => true]);
    }
}
