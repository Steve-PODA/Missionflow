<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use App\Models\Personnel;
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

        $missionsQuery = Mission::with('personnel')->orderBy('created_at', 'desc');

        if ($isTechnicien) {
            $myPersonnel = $authUser->personnel;
            if ($myPersonnel) {
                $missionsQuery->whereHas('personnel', fn($q) => $q->where('personnel.id', $myPersonnel->id));
            } else {
                $missionsQuery->whereRaw('0 = 1');
            }
        }

        $missions = $missionsQuery->get();
        $team     = $this->getTeamMembers();
        $pelotons = \App\Models\Peloton::with(['groupes.equipes'])->get();

        return Inertia::render('Missions/Index', [
            'missions' => $missions,
            'team'     => $team,
            'pelotons' => $pelotons,
        ]);
    }

    public function create()
    {
        $pelotons = \App\Models\Peloton::with(['groupes.equipes'])->get();

        return Inertia::render('Missions/MissionCreator', [
            'team'     => $this->getTeamMembers(),
            'pelotons' => $pelotons,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'briefing'       => 'nullable|string',
            'company'        => 'required|string|max:255',
            'date'           => 'required|date|after_or_equal:today',
            'startTime'      => 'required|date_format:H:i',
            'duration'       => 'required|numeric|in:0.5,1,2,4,8',
            'priority'       => 'required|in:low,medium,high,urgent',
            'location'       => 'required|string',
            'equipeSourceId' => 'nullable|exists:equipes,id',
            'chefMissionId'  => 'nullable|exists:personnel,id',
            'selectedTeam'   => 'required|array|min:1',
            'selectedTeam.*' => 'exists:personnel,id',
            'clientName'     => 'nullable|string',
            'clientEmail'    => 'nullable|email',
            'clientPhone'    => 'nullable|string',
        ], [
            'date.after_or_equal' => 'La date de la mission ne peut pas être dans le passé.',
        ]);

        $mission = Mission::create([
            'title'            => $validated['title'],
            'briefing'         => $validated['briefing'] ?? null,
            'company'          => $validated['company'],
            'date'             => $validated['date'],
            'start_time'       => $validated['startTime'],
            'duration'         => $validated['duration'],
            'priority'         => $validated['priority'],
            'location'         => $validated['location'],
            'client_name'      => $validated['clientName'] ?? null,
            'client_email'     => $validated['clientEmail'] ?? null,
            'client_phone'     => $validated['clientPhone'] ?? null,
            'status'           => 'pending',
            'equipe_source_id' => $validated['equipeSourceId'] ?? null,
        ]);

        $members = Personnel::with(['peloton', 'groupe', 'equipe'])->whereIn('id', $validated['selectedTeam'])->get();
        $chefId  = $validated['chefMissionId'] ?? $members->first()?->id;
        $syncData = [];
        foreach ($members as $p) {
            $syncData[$p->id] = [
                'role_dans_mission' => ($p->id == $chefId) ? 'chef_mission' : 'membre',
                'peloton_name'      => $p->peloton?->nom,
                'groupe_name'       => $p->groupe?->nom,
                'equipe_name'       => $p->equipe?->nom,
            ];
        }
        $mission->personnel()->sync($syncData);

        return Redirect::route('missions.index')->with('success', 'Opération déployée avec succès.');
    }

    public function edit(Mission $mission)
    {
        if (in_array($mission->status, ['completed', 'cancelled'])) {
            return redirect()->route('missions.index')
                ->with('error', 'Cette opération est terminée et ne peut plus être modifiée.');
        }

        $mission->load('personnel');
        $pelotons = \App\Models\Peloton::with(['groupes.equipes'])->get();

        return Inertia::render('Missions/MissionEditor', [
            'mission'  => $mission,
            'team'     => $this->getTeamMembers(),
            'pelotons' => $pelotons,
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
            'selectedTeam.*' => 'exists:personnel,id',
            'clientName'     => 'nullable|string',
            'clientEmail'    => 'nullable|email',
            'clientPhone'    => 'nullable|string',
        ], [
            'date.after_or_equal' => 'La date de la mission ne peut pas être dans le passé.',
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
            'client_name'  => $validated['clientName'] ?? null,
            'client_email' => $validated['clientEmail'] ?? null,
            'client_phone' => $validated['clientPhone'] ?? null,
        ]);

        $members = Personnel::with(['peloton', 'groupe', 'equipe'])->whereIn('id', $validated['selectedTeam'])->get();
        $syncData = [];
        foreach ($members as $p) {
            $existingPivot = $mission->personnel->find($p->id)?->pivot;
            $syncData[$p->id] = [
                'role_dans_mission' => $existingPivot ? $existingPivot->role_dans_mission : 'membre',
                'peloton_name'      => $p->peloton?->nom,
                'groupe_name'       => $p->groupe?->nom,
                'equipe_name'       => $p->equipe?->nom,
            ];
        }
        $mission->personnel()->sync($syncData);

        return Redirect::route('missions.index')->with('success', 'Opération mise à jour.');
    }

    public function reschedule(Request $request, Mission $mission)
    {
        $request->validate([
            'date'       => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
        ], [
            'date.after_or_equal' => 'La date de la mission ne peut pas être dans le passé.',
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
        $mission->personnel()->detach();
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
            $myPersonnel = $authUser->personnel;
            abort_if(
                !$myPersonnel || !$mission->personnel()->where('personnel.id', $myPersonnel->id)->exists(),
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

    private function getTeamMembers(): array
    {
        return Personnel::with([
                'missions' => fn($q) => $q->where('status', 'in_progress')->select('missions.id', 'missions.title'),
                'peloton',
                'groupe',
                'equipe',
                'user',
            ])
            ->get()
            ->map(fn($p) => [
                'id'              => $p->id,
                'name'            => $p->name,
                'grade'           => $p->grade,
                'fonction'        => $p->fonction,
                'avatar'          => $p->avatar,
                'availability'    => $p->availability,
                'phone_number'    => $p->phone_number,
                'email'           => $p->user?->email,
                'peloton_id'      => $p->peloton_id,
                'groupe_id'       => $p->groupe_id,
                'equipe_id'       => $p->equipe_id,
                'peloton'         => $p->peloton,
                'groupe'          => $p->groupe,
                'equipe'          => $p->equipe,
                'active_mission'  => $p->missions->first()?->only(['id', 'title']),
                'computed_status' => $p->missions->isNotEmpty() ? 'deployed' : $p->availability,
                'missions_count'  => $p->missions->count(),
            ])
            ->toArray();
    }
}
