<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PersonnelController extends Controller
{
    public function index()
    {
        $personnel = User::withCount('missions')
            ->with(['missions' => fn($q) => $q->where('status', 'in_progress')
                ->select('missions.id', 'missions.title', 'missions.location', 'missions.priority')])
            ->get()
            ->map(fn($user) => array_merge($user->toArray(), [
                'active_mission'  => $user->missions->first(),
                'computed_status' => $user->missions->isNotEmpty() ? 'deployed' : $user->availability,
            ]));

        return Inertia::render('Personnel/Index', [
            'personnel' => $personnel,
        ]);
    }

    public function updateAvailability(Request $request, User $user)
    {
        $request->validate([
            'availability' => 'required|in:available,on_leave,unavailable',
        ]);

        $oldAvailability = $user->availability;
        $user->update(['availability' => $request->availability]);

        $labels = ['available' => 'Disponible', 'on_leave' => 'En congé', 'unavailable' => 'Indisponible'];
        activity('personnel')
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties(['old' => $oldAvailability, 'new' => $request->availability])
            ->log("Disponibilité de « {$user->name} » : {$labels[$oldAvailability]} → {$labels[$request->availability]}");

        return back()->with('success', 'Disponibilité de ' . $user->name . ' mise à jour.');
    }
}
