<?php

namespace App\Console\Commands;

use App\Models\Mission;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class StartDueMissions extends Command
{
    protected $signature   = 'missions:start-due';
    protected $description = 'Passe automatiquement les missions dont l\'heure de début est atteinte au statut en opération';

    public function handle(): int
    {
        $now = Carbon::now();

        $missions = Mission::query()
            ->where('status', 'pending')
            ->where(function ($query) use ($now) {
                $query->whereDate('date', '<', $now->toDateString())
                    ->orWhere(function ($query) use ($now) {
                        $query->whereDate('date', $now->toDateString())
                            ->whereTime('start_time', '<=', $now->format('H:i:s'));
                    });
            })
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        if ($missions->isEmpty()) {
            $this->info("Aucune mission à démarrer à {$now->format('Y-m-d H:i:s')}.");

            return self::SUCCESS;
        }

        $started = 0;

        foreach ($missions as $mission) {
            $mission->update([
                'status' => 'in_progress',
            ]);

            activity('mission')
                ->performedOn($mission)
                ->withProperties([
                    'auto_started_at' => $now->toDateTimeString(),
                ])
                ->log("Opération « {$mission->title} » démarrée automatiquement");

            $this->info("[OK] « {$mission->title} » passée en opération.");
            $started++;
        }

        $this->info("Démarrage automatique terminé : {$started} mission(s) passée(s) en opération.");

        return self::SUCCESS;
    }
}
