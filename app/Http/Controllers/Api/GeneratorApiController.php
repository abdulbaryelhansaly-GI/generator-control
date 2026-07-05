<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // <-- Add this line right here
use App\Models\Generator;
use App\Models\Telemetry;
use App\Models\MaintenanceTicket;
use App\Traits\ApiResponse;

class GeneratorApiController extends Controller

    // ...
{
    // CRITICAL: This line actually gives the class access to the successResponse method
    use ApiResponse; 

    public function index()
    {
        $generators = Generator::with(['latestTelemetry', 'rulPrediction'])->get()->map(function ($gen) {
            return $this->formatGenerator($gen);
        });

        return $this->success($generators, 'Generators retrieved successfully');
    }

    // ── GET /api/generators/{id} ─────────────────────────────────
    // Single generator with full details
   public function show($id)
{
    $generator = Generator::with(['latestTelemetry', 'rulPrediction'])->find($id);

    if (!$generator) {
        return $this->error("Generator {$id} not found", 404); // ← explicit 404
    }

    return $this->success($this->formatGenerator($generator));
}
    // ── GET /api/generators/{id}/telemetry?limit=20 ──────────────
    // Recent telemetry readings (limit via query param)
    public function telemetry(Request $request, $id)
    {
        $generator = Generator::find($id);
        if (!$generator) {
            return $this->error("Generator {$id} not found");
        }

        $limit = min((int) $request->query('limit', 20), 200); // max 200 rows

        $readings = \App\Models\Telemetry::where('generator_id', $id)
            ->orderBy('recorded_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values()
            ->map(function ($r) {
                return [
                    'id'          => $r->id,
                    'rpm'         => (float) $r->rpm,
                    'temperature' => (float) $r->temperature,
                    'vibration'   => (float) $r->vibration,
                    'recorded_at' => $r->recorded_at,
                ];
            });

        return $this->success([
            'generator_id'   => (int) $id,
            'generator_name' => $generator->name,
            'count'          => $readings->count(),
            'readings'       => $readings,
        ]);
    }

    // ── GET /api/generators/{id}/anomalies?limit=20 ──────────────
    // Recent anomaly scores from Isolation Forest
    public function anomalies(Request $request, $id)
{
    $generator = Generator::find($id);
    if (!$generator) {
        return $this->error("Generator {$id} not found");
    }

    $limit = min((int) $request->query('limit', 50), 200);

    // LEFT JOIN so live telemetry rows without scores still appear
    $scores = DB::table('telemetry as t')
        ->leftJoin('anomaly_scores as a', 'a.telemetry_id', '=', 't.id')
        ->where('t.generator_id', $id)
        ->orderBy('t.recorded_at', 'desc')
        ->limit($limit)
        ->get([
            't.recorded_at as recorded_at',
            DB::raw('COALESCE(a.anomaly_score, 0) as anomaly_score'),
            DB::raw('COALESCE(a.is_anomaly, 0) as is_anomaly'),
        ])
        ->reverse()
        ->values();

    $anomalyCount = $scores->where('is_anomaly', 1)->count();

    return $this->success([
        'generator_id'   => (int) $id,
        'generator_name' => $generator->name,
        'count'          => $scores->count(),
        'anomaly_count'  => $anomalyCount,
        'anomaly_rate'   => $scores->count() > 0
            ? round($anomalyCount / $scores->count() * 100, 1)
            : 0,
        'scores'         => $scores,
    ]);
}

    // ── GET /api/generators/{id}/tickets ─────────────────────────
    // All tickets for a generator
    public function tickets($id)
    {
        $generator = Generator::find($id);
        if (!$generator) {
            return $this->error("Generator {$id} not found");
        }

        $tickets = MaintenanceTicket::where('generator_id', $id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($t) {
                return [
                    'id'                     => $t->id,
                    'title'                  => $t->title,
                    'description'            => $t->description,
                    'severity'               => $t->severity,
                    'status'                 => $t->status,
                    'triggered_automatically'=> (bool) $t->triggered_automatically,
                    'created_at'             => $t->created_at,
                    'resolved_at'            => $t->resolved_at,
                ];
            });

        return $this->success([
            'generator_id' => (int) $id,
            'count'        => $tickets->count(),
            'open'         => $tickets->where('status', '!=', 'resolved')->count(),
            'tickets'      => $tickets,
        ]);
    }

    // ── GET /api/generators/{id}/summary ─────────────────────────
    // Everything in one call — the most useful endpoint
    public function summary($id)
    {
        $generator = Generator::with('latestTelemetry')->find($id);
        if (!$generator) {
            return $this->error("Generator {$id} not found");
        }

        $openTickets = MaintenanceTicket::where('generator_id', $id)
            ->where('status', '!=', 'resolved')
            ->count();

        $latestAnomaly = DB::table('anomaly_scores')
            ->where('generator_id', $id)
            ->orderBy('detected_at', 'desc')
            ->first();

        $t = $generator->latestTelemetry;

        return $this->success([
            'generator'      => $this->formatGenerator($generator),
            'open_tickets'   => $openTickets,
            'latest_anomaly_score' => $latestAnomaly ? round($latestAnomaly->anomaly_score, 4) : null,
            'is_anomalous'   => $latestAnomaly ? (bool) $latestAnomaly->is_anomaly : false,
            'health_percent' => $t ? $this->calculateHealth($t) : null,
        ]);
    }

    // ── GET /api/tickets ─────────────────────────────────────────
    // All open tickets across all generators
    public function allTickets(Request $request)
    {
        $status = $request->query('status', 'open');

        $query = MaintenanceTicket::with('generator')
            ->orderBy('created_at', 'desc');

        if ($status !== 'all') {
            $query->where('status', $status === 'open' ? '!=' : '=', 'resolved');
        }

        $tickets = $query->get()->map(function ($t) {
            return [
                'id'                      => $t->id,
                'generator_id'            => $t->generator_id,
                'generator_name'          => $t->generator->name,
                'title'                   => $t->title,
                'severity'                => $t->severity,
                'status'                  => $t->status,
                'triggered_automatically' => (bool) $t->triggered_automatically,
                'created_at'              => $t->created_at,
                'resolved_at'             => $t->resolved_at,
            ];
        });

        return $this->success([
            'count'   => $tickets->count(),
            'tickets' => $tickets,
        ]);
    }

    // ── Private helpers ──────────────────────────────────────────

    private function formatGenerator($gen)
{
    $t   = $gen->latestTelemetry;
    $rul = $gen->rulPrediction;

    return [
        'id'           => $gen->id,
        'name'         => $gen->name,
        'location'     => $gen->location,
        'model'        => $gen->model,
        'installed_at' => $gen->installed_at,
        'status'       => $gen->status,
        'latest_reading' => $t ? [
            'rpm'         => (float) $t->rpm,
            'temperature' => (float) $t->temperature,
            'vibration'   => (float) $t->vibration,
            'recorded_at' => $t->recorded_at,
        ] : null,
        'alert_status' => $t ? $this->alertStatus($t) : 'no_data',

        // ── NEW ──────────────────────────────────────
        'rul' => $rul ? [
            'rul_cycles'          => $rul->rul_cycles,
            'rul_days'            => $rul->rul_days,
            'health_percent'      => $rul->health_percent,
            'predicted_fail_date' => $rul->predicted_fail_date,
            'limiting_sensor'     => $rul->limiting_sensor,
            'calculated_at'       => $rul->calculated_at,
        ] : null,
    ];
}
    private function alertStatus($reading)
    {
        if ($reading->temperature >= 95 || $reading->vibration >= 5.0) return 'critical';
        if ($reading->temperature >= 85 || $reading->vibration >= 3.5 || $reading->rpm >= 1600) return 'warning';
        return 'optimal';
    }

    private function calculateHealth($reading)
    {
        $tempHealth = max(0, 100 - (($reading->temperature - 70) / 25 * 100));
        $vibHealth  = max(0, 100 - (($reading->vibration - 1) / 4 * 100));
        return round(min($tempHealth, $vibHealth));
    }
}