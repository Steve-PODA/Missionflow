<?php

namespace App\Console\Commands;

use App\Models\Mission;
use App\Models\WhatsAppLog;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendMissionReminderAlerts extends Command
{
    protected $signature   = 'missions:remind {--trigger=scheduled}';
    protected $description = 'Envoie un rappel WhatsApp la veille de chaque mission planifiée';

    public function handle(WhatsAppService $whatsapp): int
    {
        $tomorrow = Carbon::tomorrow()->toDateString();
        $trigger  = $this->option('trigger');

        $missions = Mission::with('users')
            ->whereDate('date', $tomorrow)
            ->whereIn('status', ['pending', 'in_progress'])
            ->get();

        if ($missions->isEmpty()) {
            $this->info("Aucune mission demain ({$tomorrow}).");
            return self::SUCCESS;
        }

        $sent   = 0;
        $errors = 0;

        foreach ($missions as $mission) {
            $dateFormatted = Carbon::parse($mission->date)->translatedFormat('l d F Y');
            $timeFormatted = substr($mission->start_time, 0, 5);

            foreach ($mission->users as $user) {
                if (empty($user->phone_number)) {
                    $this->warn("  [SKIP] {$user->name} — pas de numéro WhatsApp.");
                    continue;
                }

                $ok = $whatsapp->sendMissionReminder(
                    to:           $user->phone_number,
                    agentName:    $user->name,
                    missionTitle: $mission->title,
                    date:         $dateFormatted,
                    time:         $timeFormatted,
                    location:     $mission->location,
                );

                WhatsAppLog::create([
                    'user_id'      => $user->id,
                    'mission_id'   => $mission->id,
                    'phone_number' => $user->phone_number,
                    'template'     => 'mission_reminder',
                    'status'       => $ok ? 'sent' : 'failed',
                    'trigger'      => $trigger,
                    'error'        => $ok ? null : 'API error — voir logs Laravel',
                ]);

                if ($ok) {
                    $this->info("  [OK] Rappel J-1 envoyé à {$user->name} pour « {$mission->title} ».");
                    $sent++;
                } else {
                    $this->error("  [ERR] Échec envoi à {$user->name} ({$user->phone_number}).");
                    $errors++;
                }
            }
        }

        $this->info("Rappels J-1 terminés : {$sent} envoyés, {$errors} erreurs.");
        return self::SUCCESS;
    }
}
