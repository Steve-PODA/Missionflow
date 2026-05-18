<?php

namespace App\Console\Commands;

use App\Models\Personnel;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CheckPersonnelLeaves extends Command
{
    protected $signature   = 'personnel:check-leaves';
    protected $description = 'Passe les agents en leave_expired lorsque leur congé est échu';

    public function handle(): void
    {
        $expired = Personnel::where('availability', 'on_leave')
            ->whereNotNull('leave_start_date')
            ->whereNotNull('leave_duration')
            ->whereNotNull('leave_unit')
            ->get()
            ->filter(fn($p) => self::isExpired($p));

        foreach ($expired as $p) {
            $p->update(['availability' => 'leave_expired']);

            activity('personnel')
                ->performedOn($p)
                ->withProperties(['old' => 'on_leave', 'new' => 'leave_expired'])
                ->log("Congé de « {$p->name} » arrivé à échéance — retour à confirmer");

            $this->line("✓ {$p->name} → leave_expired");
        }

        $this->info("{$expired->count()} agent(s) passé(s) en attente de confirmation.");
    }

    public static function isExpired(Personnel $p): bool
    {
        if (!$p->leave_start_date || !$p->leave_duration || !$p->leave_unit) {
            return false;
        }

        $end = Carbon::parse($p->leave_start_date);

        if ($p->leave_unit === 'months') {
            $end->addMonths($p->leave_duration);
        } else {
            $end->addDays($p->leave_duration);
        }

        return $end->toDateString() < now()->toDateString();
    }
}
