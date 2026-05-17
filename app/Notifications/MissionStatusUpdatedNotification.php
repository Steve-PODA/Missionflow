<?php

namespace App\Notifications;

use App\Models\Mission;
use App\Models\User;
use Illuminate\Notifications\Notification;

class MissionStatusUpdatedNotification extends Notification
{
    private static array $labels = [
        'pending'     => 'En attente',
        'in_progress' => 'En opération',
        'completed'   => 'Accomplie',
        'cancelled'   => 'Abandonnée',
    ];

    public function __construct(
        private Mission $mission,
        private User $agent,
        private string $oldStatus,
        private string $newStatus,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $old = self::$labels[$this->oldStatus] ?? $this->oldStatus;
        $new = self::$labels[$this->newStatus] ?? $this->newStatus;

        return [
            'type'       => 'mission_status_updated',
            'title'      => 'Statut mis à jour',
            'body'       => "{$this->agent->name} a changé le statut de « {$this->mission->title} » : {$old} → {$new}.",
            'action_url' => '/missions',
            'mission_id' => $this->mission->id,
        ];
    }
}
