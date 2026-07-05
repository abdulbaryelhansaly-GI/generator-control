<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Generator Report — {{ now()->format('d M Y') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #1a2535;
            padding: 32px;
            background: #fff;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid #1a2535;
        }

        .header h1 { font-size: 22px; font-weight: 700; }
        .header p  { font-size: 12px; color: #64748b; margin-top: 4px; }

        .section-title {
            font-size: 13px;
            font-weight: 700;
            margin: 20px 0 10px;
            color: #1a2535;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }

        thead tr { background: #1a2535; color: #fff; }
        thead th { padding: 9px 12px; text-align: left; font-size: 11px; }
        tbody tr { border-bottom: 1px solid #e2e8f0; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody td { padding: 8px 12px; }

        .badge {
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-ok       { background: #d1fae5; color: #065f46; }
        .badge-warning  { background: #fef3c7; color: #92400e; }
        .badge-critical { background: #fee2e2; color: #991b1b; }
        .badge-auto     { background: #e0e7ff; color: #3730a3; }

        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #1a2535;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 7px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .print-btn:hover { background: #3b82f6; }

        .footer {
            margin-top: 24px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
            font-size: 10px;
            color: #94a3b8;
            text-align: center;
        }

        @media print {
            .print-btn { display: none; }
            body { padding: 16px; }
        }
    </style>
</head>
<body>

<button class="print-btn" onclick="window.print()">🖨 Print / Save as PDF</button>

<div class="header">
    <div>
        <h1>⚙ Generator Control Center</h1>
        <p>Monitoring report — {{ now()->format('d M Y, H:i') }}</p>
    </div>
    <div style="text-align:right;">
        <p style="font-weight:600;">Total generators: {{ $generators->count() }}</p>
        <p style="color:#dc2626; font-weight:600;">Open tickets: {{ $tickets->count() }}</p>
    </div>
</div>

<div class="section-title">Generator status summary</div>
<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Location</th>
            <th>RPM</th>
            <th>Temp (°C)</th>
            <th>Vib (mm/s)</th>
            <th>Status</th>
            <th>Health</th>
            <th>RUL (days)</th>
            <th>Predicted failure</th>
            <th>Limited by</th>
        </tr>
    </thead>
    <tbody>
        @foreach($generators as $gen)
            @php
                $t   = $gen->latestTelemetry;
                $rul = $gen->rulPrediction;
                if (!$t) { $status = 'No Data'; $bc = 'badge-warning'; }
                elseif ($t->temperature >= 95 || $t->vibration >= 5.0) { $status = 'Critical'; $bc = 'badge-critical'; }
                elseif ($t->temperature >= 85 || $t->vibration >= 3.5 || $t->rpm >= 1600) { $status = 'Warning'; $bc = 'badge-warning'; }
                else { $status = 'Optimal'; $bc = 'badge-ok'; }
            @endphp
            <tr>
                <td><strong>{{ $gen->name }}</strong></td>
                <td>{{ $gen->location }}</td>
                <td>{{ $t ? number_format($t->rpm, 2) : '—' }}</td>
                <td>{{ $t ? number_format($t->temperature, 2) : '—' }}</td>
                <td>{{ $t ? number_format($t->vibration, 2) : '—' }}</td>
                <td><span class="badge {{ $bc }}">{{ $status }}</span></td>
                <td>{{ $rul ? $rul->health_percent . '%' : '—' }}</td>
                <td>{{ $rul ? round($rul->rul_days, 1) : '—' }}</td>
                <td>{{ $rul ? \Carbon\Carbon::parse($rul->predicted_fail_date)->format('d M Y') : '—' }}</td>
                <td>{{ $rul ? $rul->limiting_sensor : '—' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="section-title">Open maintenance tickets ({{ $tickets->count() }})</div>
@if($tickets->isEmpty())
    <p style="color:#059669;">No open tickets — all generators are healthy.</p>
@else
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Generator</th>
                <th>Title</th>
                <th>Severity</th>
                <th>Source</th>
                <th>Opened</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tickets as $ticket)
            <tr>
                <td>{{ $ticket->id }}</td>
                <td>{{ $ticket->generator->name }}</td>
                <td>{{ $ticket->title }}</td>
                <td><span class="badge badge-{{ $ticket->severity }}">{{ $ticket->severity }}</span></td>
                <td>
                    @if($ticket->triggered_automatically)
                        <span class="badge badge-auto">Auto</span>
                    @else Manual @endif
                </td>
                <td>{{ \Carbon\Carbon::parse($ticket->created_at)->format('d M Y H:i') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endif

<div class="footer">
    Generated by Generator Control Center &nbsp;·&nbsp;
    {{ now()->format('d M Y H:i:s') }} &nbsp;·&nbsp;
    Confidential — internal use only
</div>

</body>
</html>