<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Groupe extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'peloton_id'];

    public function peloton()
    {
        return $this->belongsTo(Peloton::class);
    }

    public function equipes()
    {
        return $this->hasMany(Equipe::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
