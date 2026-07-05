<?php

namespace App\Http\Controllers;

use App\Models\Generator;
use App\Models\MaintenanceTicket;

class ExportController extends Controller
{
    // ── PDF — rendered in browser, user prints to PDF ─────────────
    public function pdf()
    {
        $generators = Generator::with(['latestTelemetry', 'rulPrediction'])->get();
        $tickets    = MaintenanceTicket::with('generator')
            ->where('status', '!=', 'resolved')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('exports.report', compact('generators', 'tickets'));
    }

    // ── CSV download ──────────────────────────────────────────────
    public function csv()
    {
        $generators = Generator::with(['latestTelemetry', 'rulPrediction'])->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="generator-report-' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($generators) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Generator ID', 'Name', 'Location', 'Model',
                'Latest RPM', 'Temperature (°C)', 'Vibration (mm/s)',
                'Alert Status', 'Health (%)', 'RUL (days)',
                'Predicted Failure Date', 'Limiting Sensor', 'Last Reading'
            ]);

            foreach ($generators as $gen) {
                $t   = $gen->latestTelemetry;
                $rul = $gen->rulPrediction;

                if (!$t) $status = 'No Data';
                elseif ($t->temperature >= 95 || $t->vibration >= 5.0) $status = 'Critical';
                elseif ($t->temperature >= 85 || $t->vibration >= 3.5 || $t->rpm >= 1600) $status = 'Warning';
                else $status = 'Optimal';

                fputcsv($handle, [
                    $gen->id,
                    $gen->name,
                    $gen->location,
                    $gen->model,
                    $t   ? number_format($t->rpm, 2)         : '—',
                    $t   ? number_format($t->temperature, 2) : '—',
                    $t   ? number_format($t->vibration, 2)   : '—',
                    $status,
                    $rul ? $rul->health_percent               : '—',
                    $rul ? round($rul->rul_days, 1)           : '—',
                    $rul ? $rul->predicted_fail_date          : '—',
                    $rul ? $rul->limiting_sensor              : '—',
                    $t   ? $t->recorded_at                   : '—',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}