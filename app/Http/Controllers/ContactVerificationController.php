<?php

namespace App\Http\Controllers;

use App\Services\ContactVerification;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ContactVerificationController extends Controller
{
    /** Opening the link changes nothing; a scanner or preview cannot verify an address. The button does. */
    public function show(string $token, ContactVerification $verification): Response
    {
        $resolved = $verification->resolve($token);
        $change = $resolved['change'];

        return Inertia::render('Information/VerifyContact', [
            'state' => $resolved['state'],
            'company' => $change ? $change->enrollment->company_name : null,
            'event' => $change ? $change->enrollment->event->name : null,
            'name' => $change?->name,
            'url' => route('contact.verify.store', $token),
            'ui' => __('information.verify'),
        ]);
    }

    public function store(string $token, ContactVerification $verification): RedirectResponse
    {
        $verification->verify($token);

        return redirect()->route('contact.verify', $token);
    }
}
