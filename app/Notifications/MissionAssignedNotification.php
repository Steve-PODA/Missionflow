<?php

namespace App\Notifications;

use App\Models\Mission;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class MissionAssignedNotification extends Notification
{
    public function __construct(private Mission $mission) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $date = Carbon::parse($this->mission->date)->translatedFormat('d F Y');
        $time = substr($this->mission->start_time, 0, 5);

        return [
            'type'       => 'mission_assigned',
            'title'      => 'Nouvelle mission assignée',
            'body'       => "Vous êtes affecté à « {$this->mission->title} » le {$date} à {$time}.",
            'action_url' => '/missions',
            'mission_id' => $this->mission->id,
        ];
    }
}
