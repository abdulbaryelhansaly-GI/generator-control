<?php

namespace App\Observers;

use App\Mail\GeneratorAlert;
use App\Models\Generator;
use Illuminate\Support\Facades\Mail;

class GeneratorObserver
{
    public function updated(Generator $generator): void
    {
        if ($generator->wasChanged('status') && $generator->status === 'failed') {
            Mail::to(config('mail.alert_email'))->send(new GeneratorAlert(
                generatorName: $generator->name,
                alertType:     'generator_failed',
                title:         "Generator {$generator->name} has FAILED",
                description:   "Generator at {$generator->location} status changed to FAILED.",
                severity:      'critical',
                detectedAt:    now()->toDateTimeString(),
            ));
        }
    }
}