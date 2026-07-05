<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Generator extends Model
{
    const UPDATED_AT = null;
    protected $fillable = ['name', 'location', 'model', 'installed_at', 'status'];

    // One generator has many telemetry readings
    public function telemetry()
    {
        return $this->hasMany(Telemetry::class);
    }

    // One generator has many maintenance tickets
    public function maintenanceTickets()
    {
        return $this->hasMany(MaintenanceTicket::class);
    }

    // Convenience: get the latest reading for this generator
    public function latestTelemetry()
    {
        return $this->hasOne(Telemetry::class)->latestOfMany();
    }

    // Add inside the Generator class:
    public function rulPrediction()
    {
    return $this->hasOne(RulPrediction::class)->latestOfMany('calculated_at');
    }
    public $timestamps = false;
    
    
}