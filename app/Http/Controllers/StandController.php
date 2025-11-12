<?php

namespace App\Http\Controllers;

use App\Models\Stand;
use App\Models\Company;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StandController extends Controller
{
    public function index()
    {
        $stands = Stand::with('company:id,name')
            ->orderBy('id')
            ->get()
            ->map(function ($stand) {
                // Convert 100+ IDs into partner labels
                $displayId = $stand->id >= 100
                    ? 'P' . ($stand->id - 99)
                    : (string) $stand->id;

                return [
                    'id' => $stand->id,
                    'display_id' => $displayId,
                    'company_id' => $stand->company_id,
                    'company_name' => $stand->company?->name,
                ];
            });

        $companies = Company::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('dashboard/Stands', [
            'stands' => $stands,
            'companies' => $companies,
        ]);
    }

    public function view()
    {
        $stands = Stand::with('company:id,name')
            ->orderBy('id')
            ->get()
            ->map(function ($stand) {
                $displayId = $stand->id >= 100
                    ? 'P' . ($stand->id - 99)
                    : (string) $stand->id;

                return [
                    'id' => $stand->id,
                    'display_id' => $displayId,
                    'company_name' => $stand->company?->name,
                ];
            });

        return \Inertia\Inertia::render('Map', [
            'stands' => $stands,
        ]);
    }

    public function update(Request $request, Stand $stand)
    {
        // Remove the company from all other stands with the same company_id
        if ($request->company_id) {
            Stand::where('company_id', $request->company_id)
                ->where('id', '!=', $stand->id)
                ->update(['company_id' => null]);
        }

        // Assign the company to the selected stand
        $stand->update(['company_id' => $request->company_id]);

        return redirect()->back()->with('success', 'Stand updated successfully.');
    }
}
