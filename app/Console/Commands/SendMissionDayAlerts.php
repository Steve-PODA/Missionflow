<?php

namespace App\Console\Commands;

use App\Models\Mission;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendMissionDayAlerts extends Command
{
    protected $signature   = 'missions:alert-day';
    protected $description = 'Envoie une alerte WhatsApp le matin du jour de chaque mission';

    public function handle(WhatsAppService $whatsapp): int
    {
        $today = Carbon::today()->toDateString();

        $missions = Mission::with('users')
            ->whereDate('date', $today)
            ->whereIn('status', ['pending', 'in_progress'])
            ->get();

        if ($missions->isEmpty()) {
            $this->info("Aucune mission aujourd'hui ({$today}).");
            return self::SUCCESS;
        }

        $sent   = 0;
        $errors = 0;

        foreach ($missions as $mission) {
            $timeFormatted = substr($mission->start_time, 0, 5);

            foreach ($mission->users as $user) {
                if (empty($user->phone_number)) {
                    $this->warn("  [SKIP] {$user->name} — pas de numéro WhatsApp.");
                    continue;
                }

                $ok = $whatsapp->sendMissionDayAlert(
                    to:           $user->phone_number,
                    agentName:    $user->name,
                    missionTitle: $mission->title,
                    time:         $timeFormatted,
                    location:     $mission->location,
                );

                if ($ok) {
                    $this->info("  [OK] Alerte Jour J envoyée à {$user->name} pour « {$mission->title} ».");
                    $sent++;
                } else {
                    $this->error("  [ERR] Échec envoi à {$user->name} ({$user->phone_number}).");
                    $errors++;
                }
            }
        }

        $this->info("Alertes Jour J terminées : {$sent} envoyées, {$errors} erreurs.");
        return self::SUCCESS;
    }
}
