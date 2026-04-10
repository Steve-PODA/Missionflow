<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use App\Models\User;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $authUser */
        $authUser      = auth()->user();
        $isTechnicien  = $authUser->hasRole('technicien');

        // Équipe : tous les membres (utile pour tout le monde)
        $team = User::withCount('missions')
            ->with(['missions' => fn($q) => $q->where('status', 'in_progress')
                ->select('missions.id', 'missions.title')])
            ->get()
            ->map(fn($user) => array_merge($user->toArray(), [
                'active_mission'  => $user->missions->first(),
                'computed_status' => $user->missions->isNotEmpty() ? 'deployed' : $user->availability,
            ]));

        // Missions : filtrées pour les techniciens
        $missionsQuery = Mission::with('users')
            ->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc');

        if ($isTechnicien) {
            $missionsQuery->whereHas('users', fn($q) => $q->where('users.id', $authUser->id));
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
            $total      = $authUser->missions()->count();
            $inProgress = $authUser->missions()->where('status', 'in_progress')->count();
            $completed  = $authUser->missions()->where('status', 'completed')->count();
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
        $nextQuery = Mission::with('users')
            ->where('date', '>=', now()->toDateString())
            ->where('status', 'pending')
            ->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc');

        if ($isTechnicien) {
            $nextQuery->whereHas('users', fn($q) => $q->where('users.id', $authUser->id));
        }

        $nextMission = $nextQuery->first();

        // Missions en retard (pending dont la date est dépassée)
        $overdueQuery = Mission::with('users')
            ->where('status', 'pending')
            ->where('date', '<', now()->toDateString())
            ->orderBy('date', 'asc');

        if ($isTechnicien) {
            $overdueQuery->whereHas('users', fn($q) => $q->where('users.id', $authUser->id));
        }

        $overdue = $overdueQuery->get();

        return Inertia::render('Home', [
            'team'        => $team,
            'missions'    => $missions,
            'stats'       => $stats,
            'nextMission' => $nextMission,
            'overdue'     => $overdue,
        ]);
    }
}
