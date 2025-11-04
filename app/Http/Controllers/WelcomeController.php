<?php

namespace App\Http\Controllers;

use App\Models\Edition;
use Inertia\Inertia;
use Inertia\Response;

class WelcomeController extends Controller
{
    public function index(): Response
    {
        $editions = Edition::orderBy('date', 'desc')->take(3)->get();

        return Inertia::render('Welcome', [
            'editions' => $editions,
        ]);
    }
}
