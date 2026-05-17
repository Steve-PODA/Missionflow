<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class WhatsAppFailureNotification extends Notification
{
    public function __construct(
        private string $commandType, // 'remind' | 'day_alert'
        private int $failedCount,
        private int $totalCount,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $label = $this->commandType === 'remind' ? 'rappels J-1' : 'alertes Jour J';

        return [
            'type'         => 'whatsapp_failure',
            'title'        => 'Échecs WhatsApp',
            'body'         => "{$this->failedCount}/{$this->totalCount} {$label} n'ont pas été envoyés automatiquement.",
            'action_url'   => '/whatsapp',
            'action_label' => 'Déclencher manuellement',
        ];
    }
}
