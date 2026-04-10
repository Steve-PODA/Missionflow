<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'phone_number', 'role', 'availability'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('user');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return match($eventName) {
            'created' => "Compte de « {$this->name} » créé",
            'updated' => "Compte de « {$this->name} » modifié",
            'deleted' => "Compte de « {$this->name} » supprimé",
            default   => $eventName,
        };
    }

    /**
     * Les attributs assignables en masse.
     * J'ai bien inclus 'role' et 'avatar' pour correspondre à votre vue.
     */
    protected $fillable = [
        'name',
        'email',
        'phone_number',
        'password',
        'role',
        'avatar',
        'availability',
    ];

    /**
     * Les attributs cachés (pour la sécurité).
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Conversion des types (Casting).
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function missions(): BelongsToMany
    {
        return $this->belongsToMany(Mission::class, 'mission_user');
    }
}