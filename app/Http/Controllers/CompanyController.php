<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CompanyController extends Controller
{
    public function index()
    {
        $companies = Company::all();

        return Inertia::render('dashboard/Companies', [
            'companies' => $companies,
        ]);
    }

    public function create()
    {
        return Inertia::render('dashboard/CompanyCreate');
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

        $data['editions'] = $data['editions'] ?? [];
        $data['sectors'] = $data['sectors'] ?? [];
        $data['visible'] = true;

        Company::create($data);

        return redirect()->route('dashboard')->with('success', 'Company added successfully!');
    }


    public function show($id)
    {
        $company = Company::findOrFail($id);
        return inertia('dashboard/CompanyView', [
            'company' => $company,
        ]);
    }

    public function edit(Company $company)
    {
        return Inertia::render('dashboard/CompanyEdit', [
            'company' => $company,
        ]);
    }

    public function update(Request $request, Company $company)
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
            'visible' => 'required|boolean',
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $data['logo'] = '/storage/' . $path;
        }

        $data['editions'] = $data['editions'] ?? [];
        $data['sectors'] = $data['sectors'] ?? [];

        $company->update($data);

        return redirect()->route('companies')->with('success', 'Company updated successfully!');
    }

    public function destroy(Company $company)
    {
        $company->delete();

        return redirect()->route('companies')->with('success', 'Company deleted successfully!');
    }
}
