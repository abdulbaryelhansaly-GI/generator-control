@props(['generator'])
{{-- @var App\Models\Generator $generator --}}
{{-- ── Telemetry chart card ─────────────────────────────────────── --}}
<div style="background:#fff; border-radius:10px; border:1px solid #e2e8f0;
            box-shadow:0 1px 4px rgba(0,0,0,.08); padding:20px; margin-bottom:20px;">

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
        <div>
            <div style="font-weight:600; font-size:15px; color:#1a2535;">
                {{ $generator->name }} — live telemetry
            </div>
            <div style="font-size:12px; color:#94a3b8; margin-top:2px;">
                Last 20 readings · auto-refreshes every 5s
            </div>
        </div>
        <div style="font-size:12px; color:#059669; display:flex; align-items:center; gap:6px;">
            <span style="width:8px; height:8px; background:#059669; border-radius:50%;
                         display:inline-block; animation:chartPulse 2s infinite;"></span>
            Live
        </div>
    </div>

    <div style="position:relative; height:220px; width:100%;">
        <canvas id="telemetry-chart-{{ $generator->id }}"></canvas>
    </div>
</div>

{{-- ── Anomaly score chart card ─────────────────────────────────── --}}
<div style="background:#fff; border-radius:10px; border:1px solid #e2e8f0;
            box-shadow:0 1px 4px rgba(0,0,0,.08); padding:20px; margin-bottom:28px;">

    <div style="margin-bottom:16px;">
        <div style="font-weight:600; font-size:15px; color:#1a2535;">
            {{ $generator->name }} — anomaly scores
        </div>
        <div style="font-size:12px; color:#94a3b8; margin-top:2px;">
            Green = normal &nbsp;·&nbsp; Amber = borderline &nbsp;·&nbsp;
            Red = anomaly &nbsp;·&nbsp; Grey = scoring pending
        </div>
    </div>

    <div style="position:relative; height:160px; width:100%;">
        <canvas id="anomaly-chart-{{ $generator->id }}"></canvas>
    </div>
</div>

<style>
@keyframes chartPulse {
    0%, 100% { opacity: 1; }
    50%       { opacity: 0.3; }
}
</style>

<script>
(function () {
    // ── IDs scoped to this generator instance ──────────────────────
    var GEN_ID             = {{ $generator->id }};
    var TELEMETRY_CANVAS   = 'telemetry-chart-' + GEN_ID;
    var ANOMALY_CANVAS     = 'anomaly-chart-'   + GEN_ID;
    var TELEMETRY_API      = '/api/generators/' + GEN_ID + '/telemetry';
    var ANOMALY_API        = '/api/generators/' + GEN_ID + '/anomalies';
    var POLL_INTERVAL      = 5000;

    var telemetryChart = null;
    var anomalyChart   = null;

    // ── Helpers ────────────────────────────────────────────────────
    function formatTime(ts) {
        // MySQL returns "2026-07-01 13:00:00" — replace space with T for safe parsing
        var d = new Date(ts.replace(' ', 'T'));
        if (isNaN(d)) return ts.slice(11, 19); // fallback: slice raw string
        return pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
    }

    function pad(n) {
        return n.toString().padStart(2, '0');
    }

    function getCanvas(id) {
        var el = document.getElementById(id);
        if (!el) { console.warn('Canvas not found:', id); return null; }
        return el;
    }

    function safeDestroy(canvas) {
        try {
            var existing = Chart.getChart(canvas);
            if (existing) existing.destroy();
        } catch (e) { /* ignore */ }
    }

    // ── Telemetry chart ────────────────────────────────────────────
    function fetchTelemetry() {
        fetch(TELEMETRY_API)
            .then(function (r) { return r.json(); })
            .then(function (json) {
                var readings = json && json.data && json.data.readings;
                if (!readings || readings.length === 0) return;

                var labels = readings.map(function (r) { return formatTime(r.recorded_at); });
                var rpm    = readings.map(function (r) { return parseFloat(r.rpm); });
                var temp   = readings.map(function (r) { return parseFloat(r.temperature); });
                var vib    = readings.map(function (r) { return parseFloat(r.vibration); });

                if (!telemetryChart) {
                    var canvas = getCanvas(TELEMETRY_CANVAS);
                    if (!canvas) return;
                    safeDestroy(canvas);

                    telemetryChart = new Chart(canvas.getContext('2d'), {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    label:           'RPM',
                                    data:            rpm,
                                    borderColor:     '#3b82f6',
                                    backgroundColor: 'rgba(59,130,246,0.05)',
                                    borderWidth:     2,
                                    pointRadius:     2,
                                    tension:         0.3,
                                    yAxisID:         'yRpm'
                                },
                                {
                                    label:           'Temperature (°C)',
                                    data:            temp,
                                    borderColor:     '#ef4444',
                                    backgroundColor: 'rgba(239,68,68,0.05)',
                                    borderWidth:     2,
                                    pointRadius:     2,
                                    tension:         0.3,
                                    yAxisID:         'yTemp'
                                },
                                {
                                    label:           'Vibration (mm/s)',
                                    data:            vib,
                                    borderColor:     '#f59e0b',
                                    backgroundColor: 'rgba(245,158,11,0.05)',
                                    borderWidth:     2,
                                    pointRadius:     2,
                                    tension:         0.3,
                                    yAxisID:         'yTemp'
                                }
                            ]
                        },
                        options: {
                            responsive:          true,
                            maintainAspectRatio: false,
                            animation:           { duration: 300 },
                            interaction:         { mode: 'index', intersect: false },
                            plugins: {
                                legend: {
                                    position: 'top',
                                    labels:   { boxWidth: 12, font: { size: 12 } }
                                }
                            },
                            scales: {
                                x: {
                                    ticks: { font: { size: 11 }, maxTicksLimit: 8 },
                                    grid:  { color: 'rgba(0,0,0,0.04)' }
                                },
                                yRpm: {
                                    type:     'linear',
                                    position: 'left',
                                    title:    { display: true, text: 'RPM', font: { size: 11 } },
                                    ticks:    { font: { size: 11 } },
                                    grid:     { color: 'rgba(0,0,0,0.04)' }
                                },
                                yTemp: {
                                    type:     'linear',
                                    position: 'right',
                                    title:    { display: true, text: '°C / mm·s⁻¹', font: { size: 11 } },
                                    ticks:    { font: { size: 11 } },
                                    grid:     { drawOnChartArea: false }
                                }
                            }
                        }
                    });

                } else {
                    telemetryChart.data.labels           = labels;
                    telemetryChart.data.datasets[0].data = rpm;
                    telemetryChart.data.datasets[1].data = temp;
                    telemetryChart.data.datasets[2].data = vib;
                    telemetryChart.update('none');
                }
            })
            .catch(function (e) {
                console.error('[GEN-' + GEN_ID + '] Telemetry fetch error:', e);
            });
    }

    // ── Anomaly chart ──────────────────────────────────────────────
    function fetchAnomalies() {
        fetch(ANOMALY_API)
            .then(function (r) { return r.json(); })
            .then(function (json) {
                var rows = json && json.data && json.data.scores;
                if (!rows || rows.length === 0) return;

                var labels = rows.map(function (r) { return formatTime(r.recorded_at); });
                var scores = rows.map(function (r) { return parseFloat(r.anomaly_score) || 0; });
                var colors = rows.map(function (r) {
                    var score = parseFloat(r.anomaly_score) || 0;
                    var anom  = parseInt(r.is_anomaly) === 1;
                    if (anom)        return 'rgba(239,68,68,0.85)';
                    if (score > 0.55) return 'rgba(245,158,11,0.75)';
                    if (score === 0)  return 'rgba(148,163,184,0.4)';
                    return 'rgba(29,158,117,0.65)';
                });

                if (!anomalyChart) {
                    var canvas = getCanvas(ANOMALY_CANVAS);
                    if (!canvas) return;
                    safeDestroy(canvas);

                    anomalyChart = new Chart(canvas.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels:   labels,
                            datasets: [{
                                label:           'Anomaly score',
                                data:            scores,
                                backgroundColor: colors,
                                borderWidth:     0,
                                borderRadius:    3
                            }]
                        },
                        options: {
                            responsive:          true,
                            maintainAspectRatio: false,
                            animation:           { duration: 300 },
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: function (ctx) {
                                            var score = ctx.raw.toFixed(3);
                                            var row   = rows[ctx.dataIndex];
                                            if (parseFloat(score) === 0) return 'Scoring pending...';
                                            var flag = parseInt(row.is_anomaly) === 1 ? ' ⚠ ANOMALY' : '';
                                            return 'Score: ' + score + flag;
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    ticks: { font: { size: 11 }, maxTicksLimit: 8 },
                                    grid:  { display: false }
                                },
                                y: {
                                    min:   0,
                                    max:   1,
                                    ticks: { font: { size: 11 } },
                                    grid:  { color: 'rgba(0,0,0,0.04)' }
                                }
                            }
                        }
                    });

                } else {
                    anomalyChart.data.labels                      = labels;
                    anomalyChart.data.datasets[0].data            = scores;
                    anomalyChart.data.datasets[0].backgroundColor = colors;
                    anomalyChart.update('none');
                }
            })
            .catch(function (e) {
                console.error('[GEN-' + GEN_ID + '] Anomaly fetch error:', e);
            });
    }

    // ── Bootstrap: wait for Chart.js then start polling ───────────
    function waitForChartJs(callback) {
        if (typeof Chart !== 'undefined') {
            callback();
        } else {
            // Chart.js not ready yet — retry in 100ms
            setTimeout(function () { waitForChartJs(callback); }, 100);
        }
    }

    function start() {
        waitForChartJs(function () {
            fetchTelemetry();
            fetchAnomalies();
            setInterval(fetchTelemetry, POLL_INTERVAL);
            setInterval(fetchAnomalies, POLL_INTERVAL);
        });
    }

    // Start as soon as DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }

}());
</script>