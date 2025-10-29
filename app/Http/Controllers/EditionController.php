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

        return Inertia::render('Editions', [
            'editions' => $editions,
        ]);
    }

    public function index2()
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
            'images' => 'nullable|url',
            'thumbnail' => 'required|image|max:2048',
        ]);

        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('thumbnails', 'public');
            $validated['thumbnail'] = '/storage/' . $path;
        }

        $description = str_replace(["\r\n", "\r"], "\n", $validated['description']);

        $description = preg_replace("/\n{2,}/", "<br><br>", $description);
        $description = preg_replace("/(?<!<br>)\n(?!<br>)/", "<br>", $description);

        $validated['description'] = $description;

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
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'sometimes|nullable|string',
                'date' => 'sometimes|date',
                'images' => 'sometimes|nullable|url',
                'thumbnail' => 'sometimes|nullable|image|max:2048',
            ]);

            if ($request->hasFile('thumbnail')) {
                $path = $request->file('thumbnail')->store('thumbnails', 'public');
                $validated['thumbnail'] = '/storage/' . $path;
            }

            if (isset($validated['description'])) {
                $description = str_replace(["\r\n", "\r"], "\n", $validated['description']);
                $description = preg_replace("/\n{2,}/", "<br><br>", $description);
                $description = preg_replace("/(?<!<br>)\n(?!<br>)/", "<br>", $description);
                $validated['description'] = $description;
            }

            if (empty($validated)) {
                return back()->with('error', 'No data was provided to update.');
            }

            $edition->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Edition updated successfully!',
                'edition' => $edition->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the edition.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Edition $edition)
    {
        $edition->delete();

        return redirect()->route('dashEditions')->with('success', 'Edition deleted successfully!');
    }
}
