<?php

namespace App\Notifications;

use App\Models\Personnel;
use Illuminate\Notifications\Notification;

class AgentUnavailableNotification extends Notification
{
    private static array $labels = [
        'on_leave'    => 'En congé',
        'unavailable' => 'Indisponible',
    ];

    public function __construct(
        private Personnel $agent,
        private string $availability,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $label = self::$labels[$this->availability] ?? $this->availability;

        return [
            'type'       => 'agent_unavailable',
            'title'      => 'Agent indisponible',
            'body'       => "{$this->agent->name} est désormais « {$label} ». Vérifiez ses missions assignées.",
            'action_url' => '/personnel',
            'user_id'    => $this->agent->id,
        ];
    }
}
