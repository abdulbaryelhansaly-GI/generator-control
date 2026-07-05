<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FailurePrediction extends Model
{
    protected $table      = 'failure_predictions';
    protected $fillable   = [
        'generator_id','telemetry_id','twf','hdf','pwf','osf','rnf',
        'predicted_failure','failure_modes','confidence',
    ];
    public $timestamps    = false;

    const CREATED_AT = 'predicted_at';

    public function generator()
    {
        return $this->belongsTo(Generator::class);
    }
}