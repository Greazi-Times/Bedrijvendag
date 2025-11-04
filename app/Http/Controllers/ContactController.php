<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactMessage;

class ContactController extends Controller
{
    public function send(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        Mail::to('bedrijvendag.atix@avans.nl')->send(new ContactMessage($validated));

        return redirect()->back()->with('success', 'Bedankt voor je bericht! Wij nemen spoedig contact met je op.');
    }
}
