<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use App\Models\User;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $q = trim($request->get('q', ''));

        if (strlen($q) < 2) {
            return response()->json(['missions' => [], 'personnel' => []]);
        }

        /** @var \App\Models\User $authUser */
        $authUser     = auth()->user();
        $isTechnicien = $authUser->hasRole('agent');

        $missionsQuery = Mission::with('users')
            ->where(fn($query) => $query
                ->where('title',    'like', "%{$q}%")
                ->orWhere('company',  'like', "%{$q}%")
                ->orWhere('location', 'like', "%{$q}%")
            )
            ->limit(6);

        if ($isTechnicien) {
            $missionsQuery->whereHas('users', fn($q2) => $q2->where('users.id', $authUser->id));
        }

        $missions = $missionsQuery->get()->map(fn($m) => [
            'id'       => $m->id,
            'title'    => $m->title,
            'company'  => $m->company,
            'status'   => $m->status,
            'date'     => $m->date,
            'priority' => $m->priority,
        ]);

        $personnel = [];
        if ($authUser->can('view personnel')) {
            $personnel = User::where(fn($query) => $query
                ->where('name', 'like', "%{$q}%")
                ->orWhere('role', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")
            )
            ->limit(4)
            ->get()
            ->map(fn($u) => [
                'id'    => $u->id,
                'name'  => $u->name,
                'role'  => $u->role,
                'email' => $u->email,
            ]);
        }

        return response()->json([
            'missions'  => $missions,
            'personnel' => $personnel,
        ]);
    }
}
