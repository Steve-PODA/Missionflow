<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Peloton extends Model
{
    use HasFactory;

    protected $fillable = ['nom'];

    public function groupes()
    {
        return $this->hasMany(Groupe::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
