@extends('layouts.app')

@section('content')
<meta http-equiv="refresh" content="10">
<style>
    .page-title {
        font-size: 22px; font-weight: 600; margin-bottom: 24px;
        display: flex; align-items: center; gap: 10px; color: #1a1a2e;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .stat-card {
        background: #fff;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.08);
    }

    .stat-card h3 {
        font-size: 13px; color: #64748b;
        text-transform: uppercase; letter-spacing: .05em;
        margin-bottom: 12px;
    }

    .mode-bars { display: flex; flex-direction: column; gap: 8px; }

    .mode-row {
        display: flex; align-items: center; gap: 10px; font-size: 13px;
    }

    .mode-label { width: 36px; font-weight: 600; color: #1a2535; }

    .mode-bar-wrap {
        flex: 1; background: #f1f5f9; border-radius: 4px; height: 10px; overflow: hidden;
    }

    .mode-bar { height: 100%; border-radius: 4px; transition: width .3s; }

    .bar-twf { background: #ef4444; }
    .bar-hdf { background: #f97316; }
    .bar-pwf { background: #eab308; }
    .bar-osf { background: #3b82f6; }
    .bar-rnf { background: #8b5cf6; }

    .mode-count { width: 32px; text-align: right; color: #64748b; font-size: 12px; }

    .failure-rate {
        font-size: 28px; font-weight: 700; color: #dc2626; margin-bottom: 4px;
    }

    .failure-sub { font-size: 12px; color: #94a3b8; }

    .filter-card {
        background: #fff; border-radius: 10px; padding: 16px 20px;
        margin-bottom: 24px; box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end;
    }

    .filter-group { display: flex; flex-direction: column; gap: 5px; }

    .filter-group label {
        font-size: 11px; color: #64748b; font-weight: 500;
        text-transform: uppercase; letter-spacing: .05em;
    }

    .filter-group select {
        background: #f8fafc; border: 1px solid #e2e8f0;
        border-radius: 6px; padding: 7px 12px;
        font-size: 13px; color: #1a1a2e; min-width: 150px; outline: none;
    }

    .btn-filter {
        background: #3b82f6; color: #fff; border: none;
        padding: 8px 20px; border-radius: 6px; font-size: 13px;
        cursor: pointer; font-weight: 500;
    }

    .btn-reset {
        background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;
        padding: 8px 20px; border-radius: 6px; font-size: 13px;
        cursor: pointer; text-decoration: none;
    }

    .table-card {
        background: #fff; border-radius: 10px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.08); overflow: hidden;
    }

    .clf-table { width: 100%; border-collapse: collapse; font-size: 13px; }

    .clf-table thead {
        background: #1a2535; color: #94a3b8;
    }

    .clf-table thead th {
        padding: 12px 14px; text-align: left;
        font-size: 11px; text-transform: uppercase;
        letter-spacing: .06em; white-space: nowrap;
    }

    .clf-table tbody tr { border-bottom: 1px solid #f1f5f9; }
    .clf-table tbody tr:hover { background: #f8fafc; }
    .clf-table tbody td { padding: 10px 14px; color: #334155; }

    .mode-chip {
        display: inline-block; padding: 2px 8px; border-radius: 12px;
        font-size: 11px; font-weight: 600; margin-right: 3px;
    }

    .chip-twf { background: #fef2f2; color: #dc2626; }
    .chip-hdf { background: #fff7ed; color: #ea580c; }
    .chip-pwf { background: #fefce8; color: #ca8a04; }
    .chip-osf { background: #eff6ff; color: #3b82f6; }
    .chip-rnf { background: #f5f3ff; color: #7c3aed; }

    .dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 5px; }
    .dot-fail { background: #dc2626; }
    .dot-ok   { background: #16a34a; }

    .text-muted { color: #94a3b8; }
    .pagination-wrap { padding: 16px; display: flex; justify-content: flex-end; }

    .legend {
        display: flex; flex-wrap: wrap; gap: 12px;
        font-size: 12px; color: #64748b; margin-bottom: 20px;
    }
    .legend-item { display: flex; align-items: center; gap: 5px; }
    .legend-dot { width: 10px; height: 10px; border-radius: 2px; }
</style>

<div class="page-title">🔬 Failure Mode Classifier</div>
<div style="font-size:12px; color:#94a3b8; margin-bottom:16px;">
    Auto-refreshing in <span id="countdown">10</span>s
</div>
<script>
    let seconds = 10;
    setInterval(() => {
        seconds--;
        document.getElementById('countdown').textContent = seconds;
        if (seconds <= 0) location.reload();
    }, 1000);
</script>

{{-- Legend --}}
<div class="legend"></div>

{{-- Legend --}}
<div class="legend">
    <div class="legend-item"><div class="legend-dot" style="background:#ef4444"></div> TWF — Tool Wear Failure</div>
    <div class="legend-item"><div class="legend-dot" style="background:#f97316"></div> HDF — Heat Dissipation Failure</div>
    <div class="legend-item"><div class="legend-dot" style="background:#eab308"></div> PWF — Power Failure</div>
    <div class="legend-item"><div class="legend-dot" style="background:#3b82f6"></div> OSF — Overstrain Failure</div>
    <div class="legend-item"><div class="legend-dot" style="background:#8b5cf6"></div> RNF — Random Failure</div>
</div>

{{-- Stats per generator --}}
<div class="stats-grid">
    @foreach($generators as $gen)
        @php $s = $stats[$gen->id] ?? null; @endphp
        <div class="stat-card">
            <h3>{{ $gen->name }} — {{ $gen->location }}</h3>
            @if($s)
                <div class="failure-rate">
                    {{ $s->total > 0 ? round(($s->failures / $s->total) * 100, 1) : 0 }}%
                </div>
                <div class="failure-sub">failure rate ({{ $s->failures }} / {{ $s->total }} readings)</div>

                <div class="mode-bars" style="margin-top:14px;">
                    @foreach(['twf'=>'bar-twf','hdf'=>'bar-hdf','pwf'=>'bar-pwf','osf'=>'bar-osf','rnf'=>'bar-rnf'] as $mode => $barClass)
                        <div class="mode-row">
                            <span class="mode-label">{{ strtoupper($mode) }}</span>
                            <div class="mode-bar-wrap">
                                <div class="mode-bar {{ $barClass }}"
                                     style="width:{{ $s->total > 0 ? round(($s->$mode / $s->total)*100,1) : 0 }}%">
                                </div>
                            </div>
                            <span class="mode-count">{{ $s->$mode }}</span>
                        </div>
                    @endforeach
                </div>

                <div style="margin-top:12px; font-size:12px; color:#94a3b8;">
                    Avg confidence: {{ round($s->avg_confidence * 100, 1) }}%
                </div>
            @else
                <div class="text-muted" style="font-size:13px;">No predictions yet.</div>
            @endif
        </div>
    @endforeach
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('classifier.index') }}">
    <div class="filter-card">

        <div class="filter-group">
            <label>Generator</label>
            <select name="generator_id">
                <option value="">All Generators</option>
                @foreach($generators as $gen)
                    <option value="{{ $gen->id }}" {{ request('generator_id') == $gen->id ? 'selected' : '' }}>
                        {{ $gen->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="filter-group">
            <label>Failure Mode</label>
            <select name="mode">
                <option value="">All Modes</option>
                @foreach(['twf','hdf','pwf','osf','rnf'] as $m)
                    <option value="{{ $m }}" {{ request('mode') === $m ? 'selected' : '' }}>
                        {{ strtoupper($m) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="filter-group">
            <label>Show</label>
            <select name="failure_only">
                <option value="">All Readings</option>
                <option value="1" {{ request('failure_only') === '1' ? 'selected' : '' }}>Failures Only</option>
            </select>
        </div>

        <div style="display:flex; gap:8px; align-items:flex-end;">
            <button type="submit" class="btn-filter">Filter</button>
            <a href="{{ route('classifier.index') }}" class="btn-reset">Reset</a>
        </div>

    </div>
</form>

{{-- Table --}}
<div class="table-card">
    <table class="clf-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Generator</th>
                <th>Result</th>
                <th>Failure Modes</th>
                <th>TWF</th>
                <th>HDF</th>
                <th>PWF</th>
                <th>OSF</th>
                <th>RNF</th>
                <th>Confidence</th>
                <th>Predicted At</th>
            </tr>
        </thead>
        <tbody>
            @forelse($predictions as $p)
            <tr>
                <td class="text-muted">{{ $p->telemetry_id }}</td>
                <td style="font-weight:600; color:#1a2535;">{{ $p->generator->name ?? '—' }}</td>
                <td>
                    @if($p->predicted_failure)
                        <span><span class="dot dot-fail"></span>Failure</span>
                    @else
                        <span><span class="dot dot-ok"></span>Normal</span>
                    @endif
                </td>
                <td>
                    @if($p->failure_modes)
                        @foreach(explode(',', $p->failure_modes) as $mode)
                            <span class="mode-chip chip-{{ strtolower($mode) }}">{{ $mode }}</span>
                        @endforeach
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td>{{ $p->twf ? '✓' : '·' }}</td>
                <td>{{ $p->hdf ? '✓' : '·' }}</td>
                <td>{{ $p->pwf ? '✓' : '·' }}</td>
                <td>{{ $p->osf ? '✓' : '·' }}</td>
                <td>{{ $p->rnf ? '✓' : '·' }}</td>
                <td>{{ round($p->confidence * 100, 1) }}%</td>
                <td class="text-muted">{{ \Carbon\Carbon::parse($p->predicted_at)->format('Y-m-d H:i') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="11" style="text-align:center; padding:48px; color:#94a3b8;">
                    No predictions yet — make sure failure_classifier.py is running.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination-wrap">
    @if($predictions->previousPageUrl())
        <a href="{{ $predictions->previousPageUrl() }}" class="btn-reset" style="margin-right:8px;">← Prev</a>
    @endif
    @if($predictions->nextPageUrl())
        <a href="{{ $predictions->nextPageUrl() }}" class="btn-filter">Next →</a>
    @endif
    <span style="font-size:12px; color:#94a3b8; margin-left:12px; align-self:center;">
        Page {{ request('page', 1) }}
    </span>
</div>
</div>

@endsection