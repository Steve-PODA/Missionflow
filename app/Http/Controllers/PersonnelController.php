<?php

namespace App\Http\Controllers;

use App\Console\Commands\CheckPersonnelLeaves;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PersonnelController extends Controller
{
    public function index()
    {
        // Transition inline : on_leave → leave_expired si date dépassée
        User::where('availability', 'on_leave')
            ->whereNotNull('leave_start_date')
            ->get()
            ->filter(fn($u) => CheckPersonnelLeaves::isExpired($u))
            ->each(function ($u) {
                $u->update(['availability' => 'leave_expired']);
                activity('personnel')
                    ->performedOn($u)
                    ->withProperties(['old' => 'on_leave', 'new' => 'leave_expired'])
                    ->log("Congé de « {$u->name} » arrivé à échéance — retour à confirmer");
            });

        $personnel = User::withCount('missions')
            ->with(['missions' => fn($q) => $q->where('status', 'in_progress')
                ->select('missions.id', 'missions.title', 'missions.location', 'missions.priority')])
            ->get()
            ->map(fn($user) => [
                'id'               => $user->id,
                'name'             => $user->name,
                'role'             => $user->role,
                'avatar'           => $user->avatar,
                'availability'     => $user->availability,
                'leave_start_date'       => $user->leave_start_date?->toDateString(),
                'leave_duration'         => $user->leave_duration,
                'leave_unit'             => $user->leave_unit,
                'unavailability_reason'       => $user->unavailability_reason,
                'unavailability_start_date'   => $user->unavailability_start_date?->toDateString(),
                'unavailability_duration'     => $user->unavailability_duration,
                'unavailability_unit'         => $user->unavailability_unit,
                'missions_count'         => $user->missions_count,
                'active_mission'   => $user->missions->first(),
                'computed_status'  => $user->missions->isNotEmpty() ? 'deployed' : $user->availability,
            ]);

        return Inertia::render('Personnel/Index', [
            'personnel' => $personnel,
        ]);
    }

    public function updateAvailability(Request $request, User $user)
    {
        $request->validate([
            'availability'          => 'required|in:available,on_leave,unavailable',
            'leave_duration'        => 'required_if:availability,on_leave|nullable|integer|min:1|max:365',
            'leave_unit'            => 'required_if:availability,on_leave|nullable|in:days,months',
            'unavailability_reason'   => 'nullable|string|max:255',
            'unavailability_duration' => 'required_if:availability,unavailable|nullable|integer|min:1|max:365',
            'unavailability_unit'     => 'required_if:availability,unavailable|nullable|in:days,months',
        ]);

        $oldAvailability = $user->availability;

        $updateData = ['availability' => $request->availability];

        if ($request->availability === 'on_leave') {
            $updateData['leave_start_date']      = now()->toDateString();
            $updateData['leave_duration']        = $request->leave_duration;
            $updateData['leave_unit']            = $request->leave_unit;
            $updateData['unavailability_reason'] = null;
        } elseif ($request->availability === 'unavailable') {
            $updateData['unavailability_reason']     = $request->unavailability_reason;
            $updateData['unavailability_start_date'] = now()->toDateString();
            $updateData['unavailability_duration']   = $request->unavailability_duration;
            $updateData['unavailability_unit']       = $request->unavailability_unit;
            $updateData['leave_start_date']          = null;
            $updateData['leave_duration']            = null;
            $updateData['leave_unit']                = null;
        } else {
            $updateData['leave_start_date']          = null;
            $updateData['leave_duration']            = null;
            $updateData['leave_unit']                = null;
            $updateData['unavailability_reason']     = null;
            $updateData['unavailability_start_date'] = null;
            $updateData['unavailability_duration']   = null;
            $updateData['unavailability_unit']       = null;
        }

        $user->update($updateData);

        $labels = ['available' => 'Disponible', 'on_leave' => 'En congé', 'unavailable' => 'Indisponible'];
        activity('personnel')
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties(['old' => $oldAvailability, 'new' => $request->availability])
            ->log("Disponibilité de « {$user->name} » : {$labels[$oldAvailability]} → {$labels[$request->availability]}");

        return back()->with('success', 'Disponibilité de ' . $user->name . ' mise à jour.');
    }

    public function confirmReturn(User $user)
    {
        $user->update([
            'availability'               => 'available',
            'leave_start_date'           => null,
            'leave_duration'             => null,
            'leave_unit'                 => null,
            'unavailability_reason'      => null,
            'unavailability_start_date'  => null,
            'unavailability_duration'    => null,
            'unavailability_unit'        => null,
        ]);

        activity('personnel')
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties(['old' => 'leave_expired', 'new' => 'available'])
            ->log("Retour de service de « {$user->name} » confirmé");

        return back()->with('success', $user->name . ' est de retour en service.');
    }
}
