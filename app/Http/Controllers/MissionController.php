<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class MissionController extends Controller
{
    public function index()
    {
        $now = Carbon::now();

        Mission::query()
            ->where('status', 'pending')
            ->where(function ($query) use ($now) {
                $query->whereDate('date', '<', $now->toDateString())
                    ->orWhere(function ($query) use ($now) {
                        $query->whereDate('date', $now->toDateString())
                            ->whereTime('start_time', '<=', $now->format('H:i:s'));
                    });
            })
            ->update(['status' => 'in_progress']);

        /** @var \App\Models\User $authUser */
        $authUser     = Auth::user();
        $isTechnicien = $authUser->hasRole('agent');

        $missionsQuery = Mission::with('users')->orderBy('created_at', 'desc');

        if ($isTechnicien) {
            $missionsQuery->whereHas('users', fn($q) => $q->where('users.id', $authUser->id));
        }

        $missions = $missionsQuery->get();
        $team     = $this->getTeamMembers();

        return Inertia::render('Missions/Index', [
            'missions' => $missions,
            'team'     => $team,
        ]);
    }

    /**
     * Affiche le formulaire de création de mission
     */
    public function create()
    {
        return Inertia::render('Missions/MissionCreator', [
            'team' => $this->getTeamMembers(),
        ]);
    }

    /**
     * Enregistre une nouvelle mission
     */
    public function store(Request $request)
    {
        // 1. Validation rigoureuse des données provenant de Vue
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'briefing'     => 'nullable|string',
            'company'      => 'required|string|max:255',
            'date'         => 'required|date',
            'startTime'    => 'required|date_format:H:i',
            'duration'     => 'required|numeric|in:0.5,1,2,4,8',
            'priority'     => 'required|in:low,medium,high,urgent',
            'location'     => 'required|string',
            'selectedTeam' => 'required|array|min:1',
            'selectedTeam.*' => 'exists:users,id',
            'clientName'   => 'required|string',
            'clientEmail'  => 'nullable|email',
            'clientPhone'  => 'required|string',
        ]);

        $mission = Mission::create([
            'title'        => $validated['title'],
            'briefing'     => $validated['briefing'] ?? null,
            'company'      => $validated['company'],
            'date'         => $validated['date'],
            'start_time'   => $validated['startTime'],
            'duration'     => $validated['duration'],
            'priority'     => $validated['priority'],
            'location'     => $validated['location'],
            'client_name'  => $validated['clientName'],
            'client_email' => $validated['clientEmail'] ?? null,
            'client_phone' => $validated['clientPhone'],
            'status'       => 'pending',
        ]);

        // 3. Liaison avec l'équipe (Table pivot mission_user)
        // La méthode sync() prend un tableau d'IDs et s'occupe de tout
        $mission->users()->sync($validated['selectedTeam']);

        // 4. Retourner vers l'index avec un message
        return Redirect::route('missions.index')->with('success', 'Opération déployée avec succès.');
    }

    /**
     * Affiche le formulaire d'édition de mission
     */
    public function edit(Mission $mission)
    {
        if (in_array($mission->status, ['completed', 'cancelled'])) {
            return redirect()->route('missions.index')
                ->with('error', 'Cette opération est terminée et ne peut plus être modifiée.');
        }

        $mission->load('users');

        return Inertia::render('Missions/MissionEditor', [
            'mission' => $mission,
            'team'    => $this->getTeamMembers(),
        ]);
    }

    public function update(Request $request, Mission $mission)
    {
        if (in_array($mission->status, ['completed', 'cancelled'])) {
            return redirect()->route('missions.index')
                ->with('error', 'Cette opération est terminée et ne peut plus être modifiée.');
        }

        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'briefing'       => 'nullable|string',
            'company'        => 'required|string|max:255',
            'date'           => ['required', 'date', $request->date !== $mission->date ? 'after_or_equal:today' : ''],
            'startTime'      => 'required|date_format:H:i',
            'duration'       => 'required|numeric|in:0.5,1,2,4,8',
            'priority'       => 'required|in:low,medium,high,urgent',
            'location'       => 'required|string',
            'selectedTeam'   => 'required|array|min:1',
            'selectedTeam.*' => 'exists:users,id',
            'clientName'     => 'required|string',
            'clientEmail'    => 'nullable|email',
            'clientPhone'    => 'required|string',
        ]);

        $mission->update([
            'title'        => $validated['title'],
            'briefing'     => $validated['briefing'] ?? null,
            'company'      => $validated['company'],
            'date'         => $validated['date'],
            'start_time'   => $validated['startTime'],
            'duration'     => $validated['duration'],
            'priority'     => $validated['priority'],
            'location'     => $validated['location'],
            'client_name'  => $validated['clientName'],
            'client_email' => $validated['clientEmail'] ?? null,
            'client_phone' => $validated['clientPhone'],
        ]);

        $mission->users()->sync($validated['selectedTeam']);

        return Redirect::route('missions.index')->with('success', 'Opération mise à jour.');
    }

    public function reschedule(Request $request, Mission $mission)
    {
        $request->validate([
            'date'       => 'required|date',
            'start_time' => 'required|date_format:H:i',
        ]);

        $oldDate = $mission->date;
        $oldTime = substr($mission->start_time, 0, 5);

        $mission->update([
            'date'       => $request->date,
            'start_time' => $request->start_time,
        ]);

        activity('mission')
            ->causedBy(Auth::user())
            ->performedOn($mission)
            ->withProperties(['old_date' => $oldDate, 'old_time' => $oldTime, 'new_date' => $request->date, 'new_time' => $request->start_time])
            ->log("« {$mission->title} » replanifiée du {$oldDate} {$oldTime} au {$request->date} {$request->start_time}");

        return back()->with('success', "{$mission->title} reprogrammée à {$request->start_time}.");
    }

    public function destroy(Mission $mission)
    {
        $title = $mission->title;
        $mission->users()->detach();
        $mission->delete();

        return Redirect::back()->with('success', "« {$title} » a été supprimée.");
    }

    public function updateStatus(Request $request, Mission $mission)
    {
        $request->validate([
            'status' => 'required|in:pending,in_progress,completed,cancelled',
        ]);

        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        if (!$authUser->can('edit missions')) {
            abort_if(
                !$mission->users()->where('users.id', $authUser->id)->exists(),
                403,
                'Vous n\'êtes pas affecté à cette opération.'
            );
        }

        $oldStatus = $mission->status;
        $mission->update(['status' => $request->status]);

        $statusLabels = ['pending' => 'En attente', 'in_progress' => 'En opération', 'completed' => 'Accomplie', 'cancelled' => 'Abandonnée'];
        activity('mission')
            ->causedBy(Auth::user())
            ->performedOn($mission)
            ->withProperties(['old' => $oldStatus, 'new' => $request->status])
            ->log("Statut de « {$mission->title} » changé : {$statusLabels[$oldStatus]} → {$statusLabels[$request->status]}");

        return back()->with('success', 'Statut de l\'opération mis à jour.');
    }

    private function getTeamMembers()
    {
        return User::with(['missions' => fn($q) => $q->where('status', 'in_progress')
                ->select('missions.id', 'missions.title')])
            ->get()
            ->map(fn($user) => [
                'id'              => $user->id,
                'name'            => $user->name,
                'role'            => $user->role,
                'avatar'          => $user->avatar,
                'availability'    => $user->availability,
                'phone_number'    => $user->phone_number,
                'email'           => $user->email,
                'active_mission'  => $user->missions->first()?->only(['id', 'title']),
                'computed_status' => $user->missions->isNotEmpty() ? 'deployed' : $user->availability,
                'missions_count'  => $user->missions->count(),
            ]);
    }
}
