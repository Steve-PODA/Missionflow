<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Personnel extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'personnel';

    protected $fillable = [
        'name',
        'numero_incorporation',
        'grade',
        'fonction',
        'phone_number',
        'avatar',
        'peloton_id',
        'groupe_id',
        'equipe_id',
        'availability',
        'leave_start_date',
        'leave_duration',
        'leave_unit',
        'unavailability_reason',
        'unavailability_start_date',
        'unavailability_duration',
        'unavailability_unit',
        'user_id',
    ];

    protected $casts = [
        'leave_start_date'          => 'date',
        'unavailability_start_date' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'grade', 'fonction', 'availability', 'peloton_id', 'groupe_id', 'equipe_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('personnel');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return match($eventName) {
            'created' => "Personnel « {$this->name} » créé",
            'updated' => "Personnel « {$this->name} » modifié",
            'deleted' => "Personnel « {$this->name} » supprimé",
            default   => $eventName,
        };
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function peloton()
    {
        return $this->belongsTo(Peloton::class);
    }

    public function groupe()
    {
        return $this->belongsTo(Groupe::class);
    }

    public function equipe()
    {
        return $this->belongsTo(Equipe::class);
    }

    public function missions()
    {
        return $this->belongsToMany(Mission::class, 'mission_personnel')
                    ->withPivot(['role_dans_mission', 'peloton_name', 'groupe_name', 'equipe_name'])
                    ->withTimestamps();
    }

    public function chevaux()
    {
        return $this->hasMany(Cheval::class, 'cavalier_id');
    }
}
