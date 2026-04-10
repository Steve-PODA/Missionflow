<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Mission extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'status', 'priority', 'date', 'start_time', 'location', 'company'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('mission');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return match($eventName) {
            'created' => "Opération « {$this->title} » créée",
            'updated' => "Opération « {$this->title} » modifiée",
            'deleted' => "Opération « {$this->title} » supprimée",
            default   => $eventName,
        };
    }

    /**
     * Les attributs qui peuvent être assignés en masse.
     * Correspond aux champs de ton formulaire Vue.
     */
    protected $fillable = [
        'title',
        'briefing',
        'company',
        'date',
        'start_time',
        'duration',
        'location',
        'priority',
        'client_name',
        'client_email',
        'client_phone',
        'status',
    ];

    /**
     * Relation avec les membres de l'équipe (Utilisateurs).
     * Permet de lier plusieurs techniciens à une mission.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'mission_user');
    }
}