@extends('layouts.app')

@section('content')

<style>
    .status-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
        gap: 20px;
        margin-bottom: 36px;
    }

    .gen-card {
        background: #fff;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        border: 1px solid #e2e8f0;
        transition: box-shadow .2s;
    }

    .gen-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.12); }

    .gen-card-header {
        background: #1a2535;
        color: #fff;
        padding: 14px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .gen-card-header .name     { font-weight: 600; font-size: 15px; }
    .gen-card-header .location { font-size: 12px; color: #94a3b8; }

    .badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .5px;
        text-transform: uppercase;
    }

    .badge-ok       { background: #d1fae5; color: #065f46; }
    .badge-warning  { background: #fef3c7; color: #92400e; }
    .badge-critical { background: #fee2e2; color: #991b1b; }

    .gen-card-body { padding: 20px; }

    .sensor-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #f1f5f9;
        font-size: 14px;
    }

    .sensor-row:last-child { border-bottom: none; }
    .sensor-label { color: #64748b; }

    .sensor-value {
        font-weight: 600;
        font-size: 15px;
    }

    .val-ok       { color: #059669; }
    .val-warning  { color: #d97706; }
    .val-critical { color: #dc2626; }

    .rul-bar-wrap {
        background: #f1f5f9;
        border-radius: 6px;
        height: 8px;
        overflow: hidden;
    }

    .rul-bar {
        height: 100%;
        border-radius: 6px;
        transition: width .4s;
    }

    .section-title {
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 14px;
        color: #1a2535;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        font-size: 14px;
    }

    thead tr { background: #1a2535; color: #fff; }
    thead th { padding: 14px 20px; text-align: left; font-weight: 600; font-size: 13px; }
    tbody tr { border-bottom: 1px solid #f1f5f9; }
    tbody tr:last-child { border-bottom: none; }
    tbody tr:hover { background: #f8fafc; }
    tbody td { padding: 14px 20px; }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50%       { opacity: 0.3; }
    }
</style>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
    <div class="page-title" style="margin-bottom:0;">⚙ Factory Floor: Live Generator Status</div>
    <div style="display:flex; gap:10px;">
        <a href="{{ route('export.csv') }}"
           style="background:#1a2535; color:#fff; padding:8px 16px; border-radius:7px;
                  font-size:13px; font-weight:600; text-decoration:none; transition:background .2s;"
           onmouseover="this.style.background='#059669'"
           onmouseout="this.style.background='#1a2535'">
            ↓ Export CSV
        </a>
        <a href="{{ route('export.pdf') }}"
           style="background:#3b82f6; color:#fff; padding:8px 16px; border-radius:7px;
                  font-size:13px; font-weight:600; text-decoration:none; transition:background .2s;"
           onmouseover="this.style.background='#1d4ed8'"
           onmouseout="this.style.background='#3b82f6'">
            ↓ Export PDF
        </a>
    </div>
</div>
{{-- ── Status cards ───────────────────────────────────────────── --}}
<div class="status-grid">
    @foreach($generators as $gen)
        @php
            $t   = $gen->latestTelemetry;
            $rul = $gen->rulPrediction;

            // Alert status
            if (!$t) {
                $status = 'no data'; $badgeClass = 'badge-warning';
            } elseif ($t->temperature >= 95 || $t->vibration >= 5.0) {
                $status = 'critical'; $badgeClass = 'badge-critical';
            } elseif ($t->temperature >= 85 || $t->vibration >= 3.5 || $t->rpm >= 1600) {
                $status = 'warning'; $badgeClass = 'badge-warning';
            } else {
                $status = 'optimal'; $badgeClass = 'badge-ok';
            }

            // Sensor value color classes
            $tempClass = !$t ? '' : ($t->temperature >= 95 ? 'val-critical' : ($t->temperature >= 85 ? 'val-warning' : 'val-ok'));
            $vibClass  = !$t ? '' : ($t->vibration  >= 5.0 ? 'val-critical' : ($t->vibration  >= 3.5 ? 'val-warning' : 'val-ok'));
            $rpmClass  = !$t ? '' : ($t->rpm        >= 1800 ? 'val-critical' : ($t->rpm        >= 1600 ? 'val-warning' : 'val-ok'));

            // Real RUL from R predictions
            $rulPct   = $rul ? min(100, max(0, $rul->health_percent)) : 50;
            $rulColor = $rulPct > 60 ? '#059669' : ($rulPct > 30 ? '#d97706' : '#dc2626');
            $rulDays  = $rul ? round($rul->rul_days, 1)                                          : null;
            $failDate = $rul ? \Carbon\Carbon::parse($rul->predicted_fail_date)->format('d M Y') : null;
            $sensor   = $rul ? $rul->limiting_sensor                                             : null;
        @endphp

        <div class="gen-card">
            <div class="gen-card-header">
                <div>
                    <div class="name">{{ $gen->name }}</div>
                    <div class="location">{{ $gen->location }} · {{ $gen->model }}</div>
                </div>
                <span class="badge {{ $badgeClass }}">{{ $status }}</span>
            </div>

            <div class="gen-card-body">
                @if($t)
                    {{-- Sensor readings --}}
                    <div class="sensor-row">
                        <span class="sensor-label">RPM</span>
                        <span class="sensor-value {{ $rpmClass }}">{{ number_format($t->rpm, 2) }}</span>
                    </div>
                    <div class="sensor-row">
                        <span class="sensor-label">Temperature</span>
                        <span class="sensor-value {{ $tempClass }}">{{ number_format($t->temperature, 2) }} °C</span>
                    </div>
                    <div class="sensor-row">
                        <span class="sensor-label">Vibration</span>
                        <span class="sensor-value {{ $vibClass }}">{{ number_format($t->vibration, 2) }} mm/s</span>
                    </div>
                    <div class="sensor-row">
                        <span class="sensor-label">Last reading</span>
                        <span class="sensor-value" style="color:#64748b; font-weight:400;">
                            {{ $t->recorded_at ? \Carbon\Carbon::parse($t->recorded_at)->diffForHumans() : '—' }}
                        </span>
                    </div>

                    {{-- RUL section --}}
                    @if($rul)
                        <div style="margin-top:14px; padding-top:14px; border-top:1px solid #f1f5f9;">

                            <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                                <span style="font-size:12px; color:#64748b;">Remaining useful life</span>
                                <span style="font-size:12px; font-weight:600; color:{{ $rulColor }};">
                                    {{ $rulDays }} days
                                </span>
                            </div>

                            <div class="rul-bar-wrap">
                                <div class="rul-bar" style="width:{{ $rulPct }}%; background:{{ $rulColor }};"></div>
                            </div>

                            <div style="display:flex; justify-content:space-between; margin-top:8px;">
                                <span style="font-size:11px; color:#94a3b8;">
                                    Limited by: <strong>{{ $sensor }}</strong>
                                </span>
                                <span style="font-size:11px; color:#94a3b8;">
                                    Predicted failure: <strong>{{ $failDate }}</strong>
                                </span>
                            </div>

                        </div>
                    @else
                        <div style="margin-top:14px; font-size:12px; color:#94a3b8;">
                            RUL not yet calculated — run R script to update.
                        </div>
                    @endif

                @else
                    <p style="color:#94a3b8; font-size:13px;">No telemetry data yet.</p>
                @endif
            </div>
        </div>
    @endforeach
</div>

{{-- ── Live telemetry charts ──────────────────────────────────── --}}
<div class="section-title" style="margin-top:32px;">Live telemetry charts</div>

@foreach($generators as $gen)
    <x-telemetry-chart :generator="$gen" />
@endforeach

{{-- ── Summary table ─────────────────────────────────────────── --}}
<div class="section-title" style="margin-top:32px;">All generators — summary</div>
<table>
    <thead>
        <tr>
            <th>Generator ID</th>
            <th>Name</th>
            <th>Location</th>
            <th>Latest RPM</th>
            <th>Temperature (°C)</th>
            <th>Vibration (mm/s)</th>
            <th>Health</th>
            <th>RUL (days)</th>
            <th>Predicted failure</th>
            <th>Current status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($generators as $gen)
            @php
                $t   = $gen->latestTelemetry;
                $rul = $gen->rulPrediction;
            @endphp
            <tr>
                <td>{{ $gen->id }}</td>
                <td><strong>{{ $gen->name }}</strong></td>
                <td>{{ $gen->location }}</td>
                <td>{{ $t ? number_format($t->rpm, 2)         : '—' }}</td>
                <td>{{ $t ? number_format($t->temperature, 2) : '—' }}</td>
                <td>{{ $t ? number_format($t->vibration, 2)   : '—' }}</td>

                {{-- Health bar --}}
                <td>
                    @if($rul)
                        @php
                            $hp    = min(100, max(0, $rul->health_percent));
                            $hcol  = $hp > 60 ? '#059669' : ($hp > 30 ? '#d97706' : '#dc2626');
                        @endphp
                        <div style="display:flex; align-items:center; gap:8px;">
                            <div style="flex:1; background:#f1f5f9; border-radius:4px; height:6px; overflow:hidden;">
                                <div style="width:{{ $hp }}%; height:100%; background:{{ $hcol }}; border-radius:4px;"></div>
                            </div>
                            <span style="font-size:12px; color:{{ $hcol }}; font-weight:600; min-width:36px;">
                                {{ $hp }}%
                            </span>
                        </div>
                    @else
                        <span style="color:#94a3b8; font-size:12px;">—</span>
                    @endif
                </td>

                <td>
                    @if($rul)
                        <span style="font-weight:600; color:{{ $rul->rul_days < 7 ? '#dc2626' : ($rul->rul_days < 30 ? '#d97706' : '#059669') }};">
                            {{ round($rul->rul_days, 1) }}d
                        </span>
                    @else
                        <span style="color:#94a3b8;">—</span>
                    @endif
                </td>

                <td style="font-size:13px; color:#64748b;">
                    {{ $rul ? \Carbon\Carbon::parse($rul->predicted_fail_date)->format('d M Y') : '—' }}
                </td>

                <td>
                    @if(!$t)
                        <span class="badge badge-warning">No data</span>
                    @elseif($t->temperature >= 95 || $t->vibration >= 5.0)
                        <span class="badge badge-critical">Critical</span>
                    @elseif($t->temperature >= 85 || $t->vibration >= 3.5 || $t->rpm >= 1600)
                        <span class="badge badge-warning">Warning</span>
                    @else
                        <span class="badge badge-ok">Optimal</span>
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

@endsection