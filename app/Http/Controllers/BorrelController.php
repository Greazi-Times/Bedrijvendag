<?php

namespace App\Http\Controllers;

use App\Models\BorrelEnrollment;
use Illuminate\Http\Request;

class BorrelController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
            ]);

            BorrelEnrollment::create($validated);

            return back()->with('success', 'Bedankt voor je inschrijving!');
        } catch (\Throwable $e) {
            return back()->with('error', 'Er is iets misgegaan. Probeer het later opnieuw.');
        }
    }

    public function totalEnrollments()
    {
        $total = BorrelEnrollment::count();
        return response()->json(['total' => $total]);
    }
}
