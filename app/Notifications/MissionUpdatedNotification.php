<?php

namespace App\Notifications;

use App\Models\Mission;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class MissionUpdatedNotification extends Notification
{
    public function __construct(
        private Mission $mission,
        private array $changedFields, // e.g. ['date', 'heure', 'lieu']
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $fields = implode(', ', $this->changedFields);
        $date   = Carbon::parse($this->mission->date)->translatedFormat('d F Y');
        $time   = substr($this->mission->start_time, 0, 5);

        return [
            'type'       => 'mission_updated',
            'title'      => 'Mission modifiée',
            'body'       => "« {$this->mission->title} » a été mise à jour ({$fields}). Nouveau créneau : {$date} à {$time}.",
            'action_url' => '/missions',
            'mission_id' => $this->mission->id,
        ];
    }
}
