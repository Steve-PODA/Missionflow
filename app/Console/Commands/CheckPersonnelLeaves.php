<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CheckPersonnelLeaves extends Command
{
    protected $signature   = 'personnel:check-leaves';
    protected $description = 'Passe les agents en leave_expired lorsque leur congé est échu';

    public function handle(): void
    {
        $expired = User::where('availability', 'on_leave')
            ->whereNotNull('leave_start_date')
            ->whereNotNull('leave_duration')
            ->whereNotNull('leave_unit')
            ->get()
            ->filter(fn($user) => self::isExpired($user));

        foreach ($expired as $user) {
            $user->update(['availability' => 'leave_expired']);

            activity('personnel')
                ->performedOn($user)
                ->withProperties(['old' => 'on_leave', 'new' => 'leave_expired'])
                ->log("Congé de « {$user->name} » arrivé à échéance — retour à confirmer");

            $this->line("✓ {$user->name} → leave_expired");
        }

        $this->info("{$expired->count()} agent(s) passé(s) en attente de confirmation.");
    }

    public static function isExpired(User $user): bool
    {
        if (!$user->leave_start_date || !$user->leave_duration || !$user->leave_unit) {
            return false;
        }

        $end = Carbon::parse($user->leave_start_date);

        if ($user->leave_unit === 'months') {
            $end->addMonths($user->leave_duration);
        } else {
            $end->addDays($user->leave_duration);
        }

        return $end->toDateString() < now()->toDateString();
    }
}
