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

        $missionsQuery = Mission::with('personnel')
            ->where(fn($query) => $query
                ->where('title',    'like', "%{$q}%")
                ->orWhere('company',  'like', "%{$q}%")
                ->orWhere('location', 'like', "%{$q}%")
            )
            ->limit(6);

        if ($isTechnicien) {
            $myPersonnel = $authUser->personnel;
            if ($myPersonnel) {
                $missionsQuery->whereHas('personnel', fn($q2) => $q2->where('personnel.id', $myPersonnel->id));
            } else {
                $missionsQuery->whereRaw('0 = 1');
            }
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
            $personnel = \App\Models\Personnel::where(fn($query) => $query
                ->where('name',    'like', "%{$q}%")
                ->orWhere('grade',   'like', "%{$q}%")
                ->orWhere('fonction','like', "%{$q}%")
                ->orWhere('numero_incorporation', 'like', "%{$q}%")
            )
            ->limit(4)
            ->get()
            ->map(fn($p) => [
                'id'    => $p->id,
                'name'  => $p->name,
                'role'  => trim(($p->grade ?? '') . ' ' . ($p->fonction ?? '')),
                'email' => null,
            ]);
        }

        return response()->json([
            'missions'  => $missions,
            'personnel' => $personnel,
        ]);
    }
}
