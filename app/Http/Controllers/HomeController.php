<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use App\Models\Personnel;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $authUser */
        $authUser      = Auth::user();
        $isTechnicien  = $authUser->hasRole('agent');

        // Équipe : tous les membres personnel
        $team = Personnel::withCount('missions')
            ->with([
                'missions' => fn($q) => $q->where('status', 'in_progress')->select('missions.id', 'missions.title'),
                'peloton', 'groupe', 'equipe',
            ])
            ->get()
            ->map(fn($p) => [
                'id'              => $p->id,
                'name'            => $p->name,
                'grade'           => $p->grade,
                'avatar'          => $p->avatar,
                'availability'    => $p->availability,
                'phone_number'    => $p->phone_number,
                'peloton_id'      => $p->peloton_id,
                'groupe_id'       => $p->groupe_id,
                'equipe_id'       => $p->equipe_id,
                'peloton'         => $p->peloton,
                'groupe'          => $p->groupe,
                'equipe'          => $p->equipe,
                'missions_count'  => $p->missions_count,
                'active_mission'  => $p->missions->first(),
                'computed_status' => $p->missions->isNotEmpty() ? 'deployed' : $p->availability,
            ]);

        // Missions : filtrées pour les techniciens
        $missionsQuery = Mission::with('personnel')
            ->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc');

        if ($isTechnicien) {
            $missionsQuery->whereHas('personnel', fn($q) => $q->where('personnel.id', $authUser->personnel?->id ?? 0));
        }

        $missions = $missionsQuery->get()->map(function ($mission) {
            $startTime    = substr($mission->start_time, 0, 5);
            $startMinutes = (int) substr($startTime, 0, 2) * 60 + (int) substr($startTime, 3, 2);
            $endMinutes   = $startMinutes + (int) ($mission->duration * 60);
            $endTime      = sprintf('%02d:%02d', intdiv($endMinutes, 60), $endMinutes % 60);

            return array_merge($mission->toArray(), [
                'startTime' => $startTime,
                'endTime'   => $endTime,
                'type'      => ['urgent' => 'urgent', 'high' => 'maintenance', 'medium' => 'installation', 'low' => 'default'][$mission->priority] ?? 'default',
            ]);
        });

        // Stats : filtrées pour les techniciens
        if ($isTechnicien) {
            $myPersonnel = $authUser->personnel;
            $myMissions  = $myPersonnel ? $myPersonnel->missions() : Mission::whereRaw('0=1');
            $total      = $myMissions->count();
            $inProgress = (clone $myMissions)->where('status', 'in_progress')->count();
            $completed  = (clone $myMissions)->where('status', 'completed')->count();
        } else {
            $total      = Mission::count();
            $inProgress = Mission::where('status', 'in_progress')->count();
            $completed  = Mission::where('status', 'completed')->count();
        }

        $stats = [
            'total'       => $total,
            'in_progress' => $inProgress,
            'completed'   => $completed,
            'successRate' => $total > 0 ? round(($completed / $total) * 100) . '%' : '0%',
        ];

        // Prochaine mission : filtrée pour les techniciens
        $nextQuery = Mission::with('personnel')
            ->where('date', '>=', now()->toDateString())
            ->where('status', 'pending')
            ->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc');

        if ($isTechnicien) {
            $nextQuery->whereHas('personnel', fn($q) => $q->where('personnel.id', $authUser->personnel?->id ?? 0));
        }

        $nextMission = $nextQuery->first();

        // Missions en retard (pending dont la date est dépassée)
        $overdueQuery = Mission::with('personnel')
            ->where('status', 'pending')
            ->where('date', '<', now()->toDateString())
            ->orderBy('date', 'asc');

        if ($isTechnicien) {
            $overdueQuery->whereHas('personnel', fn($q) => $q->where('personnel.id', $authUser->personnel?->id ?? 0));
        }

        $overdue = $overdueQuery->get();

        $pelotons = \App\Models\Peloton::with(['groupes.equipes'])->get();

        return Inertia::render('Home', [
            'team'        => $team,
            'missions'    => $missions,
            'stats'       => $stats,
            'nextMission' => $nextMission,
            'overdue'     => $overdue,
            'pelotons'    => $pelotons,
        ]);
    }
}
