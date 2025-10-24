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
            'companies'   => Company::where('visible', true)->get(),
        ]);
    }
}
