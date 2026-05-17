<?php

namespace App\Notifications;

use App\Models\Mission;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class MissionCancelledNotification extends Notification
{
    public function __construct(private Mission $mission) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $date = Carbon::parse($this->mission->date)->translatedFormat('d F Y');

        return [
            'type'       => 'mission_cancelled',
            'title'      => 'Mission annulée',
            'body'       => "Votre mission « {$this->mission->title} » du {$date} a été annulée.",
            'action_url' => '/missions',
            'mission_id' => $this->mission->id,
        ];
    }
}
