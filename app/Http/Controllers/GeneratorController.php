<?php

namespace App\Http\Controllers;

use App\Models\Generator;
use App\Models\MaintenanceTicket;

class GeneratorController extends Controller
{
    // Return all generators with their latest telemetry reading
    public function index()
    {
    // Add rulPrediction to the with() call
    $generators = Generator::with(['latestTelemetry', 'rulPrediction'])->get();
    return view('dashboard', compact('generators'));
    }

    // Return full telemetry history for one generator
    public function show($id)
    {
        $generator = Generator::with(['telemetry' => function ($query) {
            $query->latest()->limit(100);
        }, 'maintenanceTickets'])->findOrFail($id);

        return view('generator', compact('generator'));
    }

    public function telemetryJson($id)
    {
    $readings = \App\Models\Telemetry::where('generator_id', $id)
        ->orderBy('id', 'desc')
        ->limit(20)
        ->get()
        ->reverse()
        ->values();

    return response()->json([
        'labels'      => $readings->pluck('recorded_at'),
        'rpm'         => $readings->pluck('rpm'),
        'temperature' => $readings->pluck('temperature'),
        'vibration'   => $readings->pluck('vibration'),
    ]);
    }

    public function anomalyJson($id)
    {
    $scores = \DB::table('anomaly_scores as a')
        ->join('telemetry as t', 'a.telemetry_id', '=', 't.id')
        ->where('a.generator_id', $id)
        ->orderBy('t.recorded_at', 'desc')
        ->limit(20)
        ->get(['t.recorded_at as label', 'a.anomaly_score', 'a.is_anomaly'])
        ->reverse()
        ->values();

    return response()->json($scores);
    }

}