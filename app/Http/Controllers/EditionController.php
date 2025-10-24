<?php

namespace App\Http\Controllers;

use App\Models\Edition;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EditionController extends Controller
{
    public function index()
    {
        $editions = Edition::all();

        return Inertia::render('dashboard/Editions', [
            'editions' => $editions,
        ]);
    }

    public function create()
    {
        return Inertia::render('dashboard/EditionCreate');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'date' => 'required|date',
            'images' => 'required|url',
            'thumbnail' => 'required|image|max:2048',
        ]);

        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('thumbnails', 'public');
            $validated['thumbnail'] = '/storage/' . $path;
        }

        Edition::create($validated);

        return redirect()->route('dashEditions')->with('success', 'Edition added successfully!');
    }

    public function show(Edition $edition)
    {
        return Inertia::render('dashboard/EditionView', [
            'edition' => $edition,
        ]);
    }

    public function edit(Edition $edition)
    {
        return Inertia::render('dashboard/EditionEdit', [
            'edition' => $edition,
        ]);
    }

    public function update(Request $request, Edition $edition)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'images' => 'nullable|url',
            'thumbnail' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('thumbnails', 'public');
            $validated['thumbnail'] = '/storage/' . $path;
        }

        $edition->update($validated);

        return redirect()->route('dashEditions')->with('success', 'Edition updated successfully!');
    }

    public function destroy(Edition $edition)
    {
        $edition->delete();

        return redirect()->route('dashEditions')->with('success', 'Edition deleted successfully!');
    }
}
