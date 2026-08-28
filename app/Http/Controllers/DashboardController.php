<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Dashboard', [
            'stats' => [
                'totalUsers' => User::count(),
                'activeUsers' => User::where('is_active', true)->count(),
                'totalRoles' => Role::count(),
            ],
            'usersByRole' => Role::query()
                ->withCount('users')
                ->orderByDesc('users_count')
                ->get()
                ->map(fn (Role $role) => ['role' => $role->name, 'count' => $role->users_count]),
        ]);
    }
}
