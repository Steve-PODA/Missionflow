<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppLog extends Model
{
    protected $table = 'whatsapp_logs';
    protected $fillable = ['user_id', 'mission_id', 'phone_number', 'template', 'status', 'trigger', 'error'];

    public function user()    { return $this->belongsTo(User::class); }
    public function mission() { return $this->belongsTo(Mission::class); }
}
