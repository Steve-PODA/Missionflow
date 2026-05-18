<?php

namespace App\Console\Commands;

use App\Models\Mission;
use App\Models\User;
use App\Models\WhatsAppLog;
use App\Notifications\WhatsAppFailureNotification;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class SendMissionReminderAlerts extends Command
{
    protected $signature   = 'missions:remind {--trigger=scheduled}';
    protected $description = 'Envoie un rappel WhatsApp la veille de chaque mission planifiée';

    public function handle(WhatsAppService $whatsapp): int
    {
        $tomorrow = Carbon::tomorrow()->toDateString();
        $trigger  = $this->option('trigger');

        $missions = Mission::with('personnel')
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

            foreach ($mission->personnel as $membre) {
                if (empty($membre->phone_number)) {
                    $this->warn("  [SKIP] {$membre->name} — pas de numéro WhatsApp.");
                    continue;
                }

                $ok = $whatsapp->sendMissionReminder(
                    to:           $membre->phone_number,
                    agentName:    $membre->name,
                    missionTitle: $mission->title,
                    date:         $dateFormatted,
                    time:         $timeFormatted,
                    location:     $mission->location,
                );

                WhatsAppLog::create([
                    'user_id'      => $membre->user_id,
                    'mission_id'   => $mission->id,
                    'phone_number' => $membre->phone_number,
                    'template'     => 'mission_reminder',
                    'status'       => $ok ? 'sent' : 'failed',
                    'trigger'      => $trigger,
                    'error'        => $ok ? null : 'API error — voir logs Laravel',
                ]);

                if ($ok) {
                    $this->info("  [OK] Rappel J-1 envoyé à {$membre->name} pour « {$mission->title} ».");
                    $sent++;
                } else {
                    $this->error("  [ERR] Échec envoi à {$membre->name} ({$membre->phone_number}).");
                    $errors++;
                }
            }
        }

        $this->info("Rappels J-1 terminés : {$sent} envoyés, {$errors} erreurs.");

        if ($errors > 0 && $trigger === 'scheduled') {
            $adminsManagers = User::role(['admin', 'manager'])->get();
            Notification::send($adminsManagers, new WhatsAppFailureNotification('remind', $errors, $sent + $errors));
        }

        return self::SUCCESS;
    }
}
