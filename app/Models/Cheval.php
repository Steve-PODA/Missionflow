<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Cheval extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'chevaux';

    protected $fillable = [
        'numero_incorporation',
        'cavalier_id',
        'statut',
        'disponibilite',
        'indisponibilite_motif',
        'indisponibilite_debut',
        'indisponibilite_duree',
        'indisponibilite_unite',
    ];

    protected $casts = [
        'indisponibilite_debut' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['numero_incorporation', 'cavalier_id', 'statut', 'disponibilite'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('cheval');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return match($eventName) {
            'created' => "Cheval « {$this->numero_incorporation} » enregistré",
            'updated' => "Cheval « {$this->numero_incorporation} » modifié",
            'deleted' => "Cheval « {$this->numero_incorporation} » supprimé",
            default   => $eventName,
        };
    }

    public function cavalier()
    {
        return $this->belongsTo(Personnel::class, 'cavalier_id');
    }
}
