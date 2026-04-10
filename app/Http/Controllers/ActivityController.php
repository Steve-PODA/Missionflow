<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $query = Activity::with('causer')
            ->latest()
            ->limit(200);

        if ($request->filled('log_name')) {
            $query->where('log_name', $request->log_name);
        }

        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->causer_id)
                  ->where('causer_type', \App\Models\User::class);
        }

        $activities = $query->get()->map(fn($a) => [
            'id'          => $a->id,
            'log_name'    => $a->log_name,
            'description' => $a->description,
            'causer'      => $a->causer ? [
                'id'   => $a->causer->id,
                'name' => $a->causer->name,
                'role' => $a->causer->role,
            ] : null,
            'properties'  => $a->properties,
            'created_at'  => $a->created_at->format('Y-m-d H:i:s'),
        ]);

        $users = \App\Models\User::orderBy('name')->get(['id', 'name', 'role']);

        return Inertia::render('Activity/Index', [
            'activities' => $activities,
            'users'      => $users,
            'filters'    => $request->only(['log_name', 'causer_id']),
        ]);
    }
}
