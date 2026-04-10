<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index()
    {
        // ── KPIs globaux ──────────────────────────────────────────
        $total     = Mission::count();
        $completed = Mission::where('status', 'completed')->count();
        $cancelled = Mission::where('status', 'cancelled')->count();
        $active    = Mission::where('status', 'in_progress')->count();

        $kpis = [
            'total'       => $total,
            'completed'   => $completed,
            'cancelled'   => $cancelled,
            'active'      => $active,
            'successRate' => $total > 0 ? round(($completed / $total) * 100) : 0,
        ];

        // ── Missions par mois (6 derniers mois) ───────────────────
        $monthNames = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $date  = now()->subMonths($i);
            $year  = $date->year;
            $month = $date->month;

            $counts = Mission::whereYear('date', $year)
                ->whereMonth('date', $month)
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status');

            $months[] = [
                'label'      => $monthNames[$month - 1] . ' ' . $year,
                'total'      => $counts->sum(),
                'completed'  => $counts->get('completed', 0),
                'cancelled'  => $counts->get('cancelled', 0),
                'in_progress'=> $counts->get('in_progress', 0),
                'pending'    => $counts->get('pending', 0),
            ];
        }

        // ── Stats par agent ───────────────────────────────────────
        $agents = User::withCount([
                'missions',
                'missions as completed_count' => fn($q) => $q->where('status', 'completed'),
                'missions as cancelled_count' => fn($q) => $q->where('status', 'cancelled'),
                'missions as active_count'    => fn($q) => $q->where('status', 'in_progress'),
            ])
            ->orderByDesc('missions_count')
            ->get()
            ->map(fn($u) => [
                'id'          => $u->id,
                'name'        => $u->name,
                'role'        => $u->role,
                'total'       => $u->missions_count,
                'completed'   => $u->completed_count,
                'cancelled'   => $u->cancelled_count,
                'active'      => $u->active_count,
                'successRate' => $u->missions_count > 0
                    ? round(($u->completed_count / $u->missions_count) * 100)
                    : 0,
            ]);

        // ── Répartition par priorité ──────────────────────────────
        $byPriority = Mission::selectRaw('priority, count(*) as count')
            ->groupBy('priority')
            ->pluck('count', 'priority');

        $priorities = collect(['urgent', 'high', 'medium', 'low'])->map(fn($p) => [
            'key'   => $p,
            'label' => ['urgent' => 'CRITIQUE', 'high' => 'Haute', 'medium' => 'Standard', 'low' => 'Faible'][$p],
            'count' => $byPriority->get($p, 0),
        ]);

        // ── Répartition par statut ────────────────────────────────
        $byStatus = Mission::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $statuses = collect(['pending', 'in_progress', 'completed', 'cancelled'])->map(fn($s) => [
            'key'   => $s,
            'label' => ['pending' => 'En attente', 'in_progress' => 'En opération', 'completed' => 'Accomplies', 'cancelled' => 'Abandonnées'][$s],
            'count' => $byStatus->get($s, 0),
        ]);

        return Inertia::render('Reports/Index', [
            'kpis'       => $kpis,
            'months'     => $months,
            'agents'     => $agents,
            'priorities' => $priorities,
            'statuses'   => $statuses,
        ]);
    }

    public function export(): StreamedResponse
    {
        $missions = Mission::with('users')->orderBy('date', 'desc')->get();

        $statusLabels   = ['pending' => 'En attente', 'in_progress' => 'En opération', 'completed' => 'Accomplie', 'cancelled' => 'Abandonnée'];
        $priorityLabels = ['urgent' => 'CRITIQUE', 'high' => 'Haute', 'medium' => 'Standard', 'low' => 'Faible'];

        return response()->streamDownload(function () use ($missions, $statusLabels, $priorityLabels) {
            $out = fopen('php://output', 'w');
            // BOM UTF-8 pour Excel
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($out, ['Titre', 'Commanditaire', 'Date', 'Heure', 'Durée (h)', 'Lieu', 'Priorité', 'Statut', 'Agents', 'Officier de liaison', 'Téléphone'], ';');

            foreach ($missions as $m) {
                fputcsv($out, [
                    $m->title,
                    $m->company,
                    $m->date,
                    substr($m->start_time, 0, 5),
                    $m->duration,
                    $m->location,
                    $priorityLabels[$m->priority] ?? $m->priority,
                    $statusLabels[$m->status]     ?? $m->status,
                    $m->users->pluck('name')->join(', '),
                    $m->client_name,
                    $m->client_phone,
                ], ';');
            }

            fclose($out);
        }, 'missionflow-export-' . now()->format('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
