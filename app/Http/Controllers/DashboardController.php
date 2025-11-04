<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Company;
use App\Models\User;
use App\Models\BorrelEnrollment;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboard', [
            'companyStats' => [
                'visible' => Company::where('visible', true)->count(),
                'hidden' => Company::where('visible', false)->count(),
            ],
            'borrelCount' => BorrelEnrollment::count(),
            'userCount' => [
                'total' => User::count(),
                'admin' => User::where('role', 'admin')->count(),
            ],
            'companies' => Company::with(['editions', 'sectors'])
                ->where('visible', true)
                ->get()
                ->map(function ($company) {
                    return [
                        'id' => $company->id,
                        'name' => $company->name,
                        'href' => $company->href,
                        'logo' => $company->logo,
                        'description' => $company->description,
                        'visible' => $company->visible,
                        'editions' => $company->editions->pluck('name')->toArray(),
                        'sectors' => $company->sectors->pluck('name')->toArray(),
                    ];
                }),
        ]);
    }
}
