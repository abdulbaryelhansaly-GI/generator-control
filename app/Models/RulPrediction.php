<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RulPrediction extends Model
{
    protected $table    = 'rul_predictions';
    protected $fillable = [
        'generator_id', 'current_cycle', 'predicted_fail_cycle',
        'rul_cycles', 'rul_days', 'predicted_fail_date',
        'limiting_sensor', 'health_percent'
    ];

    protected $casts = [
        'predicted_fail_date' => 'datetime',
        'health_percent'      => 'float',
        'rul_days'            => 'float',
    ];

    public function generator()
    {
        return $this->belongsTo(Generator::class);
    }
}