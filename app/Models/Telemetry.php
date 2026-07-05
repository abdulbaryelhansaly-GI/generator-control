<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Telemetry extends Model
{
    const CREATED_AT = 'recorded_at';  // ← add this
    const UPDATED_AT = null;
    protected $table = 'telemetry';
    protected $fillable = ['generator_id', 'rpm', 'temperature', 'vibration'];

    // Each reading belongs to one generator
    public function generator()
    {
        return $this->belongsTo(Generator::class);
    }
}