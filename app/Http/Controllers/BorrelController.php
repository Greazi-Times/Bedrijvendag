<?php

namespace App\Http\Controllers;

use App\Models\BorrelEnrollment;
use Illuminate\Http\Request;

class BorrelController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        BorrelEnrollment::create($validated);

        return redirect()->back()->with('success', 'Bedankt voor je inschrijving!');
    }

    public function totalEnrollments()
    {
        $total = BorrelEnrollment::count();
        return response()->json(['total' => $total]);
    }
}
