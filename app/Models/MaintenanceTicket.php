<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceTicket extends Model
{
    const UPDATED_AT = null;
    protected $fillable = [
        'generator_id', 'title', 'description',
        'severity', 'status', 'triggered_automatically', 'resolved_at'
    ];

    public function generator()
    {
        return $this->belongsTo(Generator::class);
    }
}