<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
        .card { background: #fff; border-radius: 8px; padding: 30px; max-width: 600px; margin: auto; }
        .badge-critical { background: #dc2626; color: white; padding: 4px 10px; border-radius: 4px; font-size: 13px; }
        .badge-high     { background: #ea580c; color: white; padding: 4px 10px; border-radius: 4px; font-size: 13px; }
        .badge-failed   { background: #7c3aed; color: white; padding: 4px 10px; border-radius: 4px; font-size: 13px; }
        .label { color: #6b7280; font-size: 13px; margin-top: 16px; }
        .value { color: #111827; font-size: 15px; margin-top: 2px; }
        .footer { margin-top: 30px; font-size: 12px; color: #9ca3af; text-align: center; }
        h2 { color: #111827; }
    </style>
</head>
<body>
    <div class="card">
        <h2>⚠️ Generator Alert</h2>

        <p>
            @if($alertType === 'critical_ticket')
                <span class="badge-critical">CRITICAL TICKET</span>
            @elseif($alertType === 'high_ticket')
                <span class="badge-high">HIGH SEVERITY</span>
            @else
                <span class="badge-failed">GENERATOR FAILED</span>
            @endif
        </p>

        <div class="label">Generator</div>
        <div class="value">{{ $generatorName }}</div>

        <div class="label">Alert</div>
        <div class="value">{{ $title }}</div>

        <div class="label">Description</div>
        <div class="value">{{ $description }}</div>

        <div class="label">Severity</div>
        <div class="value">{{ strtoupper($severity) }}</div>

        <div class="label">Detected At</div>
        <div class="value">{{ $detectedAt }}</div>

        <div class="footer">
            Generator Monitoring System — automated alert<br>
            Do not reply to this email.
        </div>
    </div>
</body>
</html>