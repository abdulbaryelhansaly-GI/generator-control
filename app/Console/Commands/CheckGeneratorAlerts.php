<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Generator;
use App\Models\MaintenanceTicket;
use Illuminate\Support\Facades\Mail;
use App\Mail\GeneratorAlert;

class CheckGeneratorAlerts extends Command
{
    protected $signature   = 'generators:check-alerts';
    protected $description = 'Check latest telemetry and trigger maintenance tickets on threshold breach';

    const TEMP_CRITICAL = 37.0;
    const VIB_CRITICAL  = 7.0;
    const RPM_MAX       = 2000;

    public function handle()
    {
        $generators = Generator::with('latestTelemetry')->get();

        foreach ($generators as $generator) {
            $reading = $generator->latestTelemetry;

            if (!$reading) {
                continue;
            }

            $alerts = [];

            if ($reading->temperature >= self::TEMP_CRITICAL) {
                $alerts[] = [
                    'title'       => "Critical temperature on {$generator->name}",
                    'description' => "Temperature reached {$reading->temperature}°C (threshold: " . self::TEMP_CRITICAL . "°C).",
                    'severity'    => 'critical',
                ];
            }

            if ($reading->vibration >= self::VIB_CRITICAL) {
                $alerts[] = [
                    'title'       => "Excessive vibration on {$generator->name}",
                    'description' => "Vibration reached {$reading->vibration} mm/s (threshold: " . self::VIB_CRITICAL . " mm/s).",
                    'severity'    => 'critical',
                ];
            }

            if ($reading->rpm >= self::RPM_MAX) {
                $alerts[] = [
                    'title'       => "RPM overspeed on {$generator->name}",
                    'description' => "RPM reached {$reading->rpm} (threshold: " . self::RPM_MAX . ").",
                    'severity'    => 'high',
                ];
            }

            foreach ($alerts as $alert) {

                $alreadyOpen = MaintenanceTicket::where('generator_id', $generator->id)
                    ->where('title', $alert['title'])
                    ->where('status', '!=', 'resolved')
                    ->exists();

                $resolvedRecently = MaintenanceTicket::where('generator_id', $generator->id)
                    ->where('title', $alert['title'])
                    ->where('status', 'resolved')
                    ->where('resolved_at', '>=', $reading->recorded_at)
                    ->exists();

                if (in_array($alert['severity'], ['critical', 'high'])) {
                    Mail::to(config('mail.alert_email'))->send(new GeneratorAlert(
                        generatorName: $generator->name,
                        alertType:     $alert['severity'] === 'critical' ? 'critical_ticket' : 'high_ticket',
                        title:         $alert['title'],
                        description:   $alert['description'],
                        severity:      $alert['severity'],
                        detectedAt:    now()->toDateTimeString(),
                    ));

                    $this->info("Alert email sent for: {$alert['title']}");
                }
            }
            
        }
        

        $this->info('Alert check complete.');
        
    }
    
}