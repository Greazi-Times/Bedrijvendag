<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CompanyController extends Controller
{
    public function index()
    {
        $companies = Company::with(['editions', 'sectors'])->get()->map(function ($company) {
            return [
                'id' => $company->id,
                'name' => $company->name,
                'visible' => $company->visible,
                'editions' => $company->editions->pluck('name')->toArray(),
                'sectors' => $company->sectors->pluck('name')->toArray(),
            ];
        });

        return Inertia::render('dashboard/Companies', [
            'companies' => $companies,
        ]);
    }

    public function publicIndex()
    {
        $companiesCollection = Company::where('visible', true)
            ->with(['editions:id,name', 'sectors:id,name'])
            ->get();

        // Load stands for these companies and key by company_id
        $stands = \App\Models\Stand::whereIn('company_id', $companiesCollection->pluck('id'))->get()->keyBy('company_id');

        $companies = $companiesCollection->map(function ($company) use ($stands) {
            $stand = $stands->get($company->id);
            $standDisplay = null;
            if ($stand) {
                $standDisplay = $stand->id >= 100
                    ? 'P' . ($stand->id - 99)
                    : (string) $stand->id;
            }

            return [
                'id' => $company->id,
                'name' => $company->name,
                'description' => is_array($company->description)
                    ? $company->description
                    : [$company->description],
                'logo' => $company->logo,
                'href' => $company->href,
                'educations' => $company->sectors->pluck('name')->toArray(),
                'visible' => $company->visible,
                'stand_display' => $standDisplay,
            ];
        });

        return inertia('Companies', [
            'companies' => $companies,
        ]);
    }

    public function create()
    {
        return Inertia::render('dashboard/CompanyCreate', [
            'editionsOptions' => \App\Models\Edition::select('name')->orderBy('name')->pluck('name'),
            'sectorOptions' => \App\Models\Sector::select('name')->orderBy('name')->pluck('name'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'href' => 'nullable|url|max:255',
            'editions' => 'nullable|array',
            'editions.*' => 'string|max:50',
            'sectors' => 'nullable|array',
            'sectors.*' => 'string|max:50',
            'logo' => 'nullable|image|max:2048', // 2MB max
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $data['logo'] = '/storage/' . $path;
        }

        $data['visible'] = true;
        $description = str_replace(["\r\n", "\r"], "\n", $data['description']);

        $description = preg_replace("/\n{2,}/", "<br><br>", $description);
        $description = preg_replace("/(?<!<br>)\n(?!<br>)/", "<br>", $description);

        $data['description'] = $description;


        $company = Company::create($data);

        if (!empty($data['editions'])) {
            $editionIds = \App\Models\Edition::whereIn('name', $data['editions'])->pluck('id');
            $company->editions()->sync($editionIds);
        }
        if (!empty($data['sectors'])) {
            $sectorIds = \App\Models\Sector::whereIn('name', $data['sectors'])->pluck('id');
            $company->sectors()->sync($sectorIds);
        }

        return redirect()->route('dashboard')->with('success', 'Company added successfully!');
    }


    public function show($id)
    {
        $company = Company::with(['editions', 'sectors'])->findOrFail($id);
        return inertia('dashboard/CompanyView', [
            'company' => $company,
            'editionsOptions' => \App\Models\Edition::select('name')->orderBy('name')->pluck('name'),
            'sectorOptions' => \App\Models\Sector::select('name')->orderBy('name')->pluck('name'),
        ]);
    }

    public function edit(Company $company)
    {
        $company->load(['editions:name', 'sectors:name']);

        return Inertia::render('dashboard/CompanyEdit', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'description' => $company->description,
                'href' => $company->href,
                'logo' => $company->logo,
                'visible' => $company->visible,
                'editions' => $company->editions->pluck('name')->toArray(),
                'sectors' => $company->sectors->pluck('name')->toArray(),
            ],
            'editions' => \App\Models\Edition::select('name')->orderBy('name')->pluck('name'),
            'sectors' => \App\Models\Sector::select('name')->orderBy('name')->pluck('name'),
        ]);
    }

    public function update(Request $request, Company $company)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'href' => 'sometimes|url|max:255',
            'editions' => 'sometimes|array',
            'editions.*' => 'sometimes|string|max:50',
            'sectors' => 'sometimes|array',
            'sectors.*' => 'sometimes|string|max:50',
            'logo' => 'sometimes|image|max:2048', // 2MB max
            'visible' => 'sometimes|boolean',
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $data['logo'] = '/storage/' . $path;
        }

        if (isset($data['description'])) {
            $description = str_replace(["\r\n", "\r"], "\n", $data['description']);
            $description = preg_replace("/\n{2,}/", "<br><br>", $description);
            $description = preg_replace("/(?<!<br>)\n(?!<br>)/", "<br>", $description);
            $data['description'] = $description;
        }

        $company->update($data);

        if (!empty($data['editions'])) {
            $editionIds = \App\Models\Edition::whereIn('name', $data['editions'])->pluck('id');
            $company->editions()->sync($editionIds);
        }
        if (!empty($data['sectors'])) {
            $sectorIds = \App\Models\Sector::whereIn('name', $data['sectors'])->pluck('id');
            $company->sectors()->sync($sectorIds);
        }

        return redirect()->route('companies')->with('success', 'Company updated successfully!');
    }

    public function destroy(Company $company)
    {
        $company->delete();

        return redirect()->route('dashboard')->with('success', 'Company deleted successfully!');
    }
}
