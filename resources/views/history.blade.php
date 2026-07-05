@extends('layouts.app')

@section('content')

<style>
    .history-header {
        font-size: 22px;
        font-weight: 600;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 10px;
        color: #1a1a2e;
    }

    .filter-card {
        background: #fff;
        border-radius: 10px;
        padding: 20px 24px;
        margin-bottom: 24px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        align-items: flex-end;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .filter-group label {
        font-size: 12px;
        color: #64748b;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .filter-group select,
    .filter-group input[type="date"] {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 7px 12px;
        font-size: 13px;
        color: #1a1a2e;
        min-width: 140px;
        outline: none;
        transition: border-color .2s;
    }

    .filter-group select:focus,
    .filter-group input[type="date"]:focus {
        border-color: #3b82f6;
    }

    .btn-filter {
        background: #3b82f6;
        color: #fff;
        border: none;
        padding: 8px 20px;
        border-radius: 6px;
        font-size: 13px;
        cursor: pointer;
        font-weight: 500;
        transition: background .2s;
    }

    .btn-filter:hover { background: #2563eb; }

    .btn-reset {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
        padding: 8px 20px;
        border-radius: 6px;
        font-size: 13px;
        cursor: pointer;
        font-weight: 500;
        text-decoration: none;
        transition: background .2s;
    }

    .btn-reset:hover { background: #e2e8f0; }

    .table-card {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        overflow: hidden;
    }

    .history-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }

    .history-table thead {
        background: #1a2535;
        color: #94a3b8;
    }

    .history-table thead th {
        padding: 12px 16px;
        text-align: left;
        font-weight: 500;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        white-space: nowrap;
    }

    .history-table tbody tr {
        border-bottom: 1px solid #f1f5f9;
        transition: background .15s;
    }

    .history-table tbody tr:hover { background: #f8fafc; }

    .history-table tbody td {
        padding: 12px 16px;
        color: #334155;
        white-space: nowrap;
    }

    .badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .badge-critical { background: #fef2f2; color: #dc2626; }
    .badge-high     { background: #fff7ed; color: #ea580c; }
    .badge-medium   { background: #fefce8; color: #ca8a04; }
    .badge-low      { background: #f0fdf4; color: #16a34a; }

    .badge-open        { background: #eff6ff; color: #3b82f6; }
    .badge-in_progress { background: #fefce8; color: #ca8a04; }
    .badge-resolved    { background: #f0fdf4; color: #16a34a; }

    .badge-auto   { background: #f5f3ff; color: #7c3aed; }
    .badge-manual { background: #f8fafc; color: #94a3b8; }

    .gen-name {
        font-weight: 600;
        color: #1a2535;
    }

    .text-muted { color: #94a3b8; }

    .pagination-wrap {
        padding: 16px;
        display: flex;
        justify-content: flex-end;
    }

    .empty-row td {
        text-align: center;
        padding: 48px;
        color: #94a3b8;
        font-size: 14px;
    }

    .stats-bar {
        display: flex;
        gap: 16px;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }

    .stat-pill {
        background: #fff;
        border-radius: 8px;
        padding: 12px 20px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        font-size: 13px;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .stat-pill strong {
        font-size: 18px;
        color: #1a1a2e;
    }
</style>

<div class="history-header">🗂 Maintenance History</div>

{{-- Stats bar --}}
<div class="stats-bar">
    <div class="stat-pill">
        <strong>{{ $tickets->total() }}</strong> total tickets
    </div>
    <div class="stat-pill">
        <strong style="color:#3b82f6">{{ $tickets->getCollection()->where('status','open')->count() }}</strong> open
    </div>
    <div class="stat-pill">
        <strong style="color:#16a34a">{{ $tickets->getCollection()->where('status','resolved')->count() }}</strong> resolved
    </div>
    <div class="stat-pill">
        <strong style="color:#dc2626">{{ $tickets->getCollection()->where('severity','critical')->count() }}</strong> critical
    </div>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('tickets.history') }}">
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
            <label>Severity</label>
            <select name="severity">
                <option value="">All Severities</option>
                @foreach(['low','medium','high','critical'] as $s)
                    <option value="{{ $s }}" {{ request('severity') === $s ? 'selected' : '' }}>
                        {{ ucfirst($s) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="filter-group">
            <label>Status</label>
            <select name="status">
                <option value="">All Statuses</option>
                @foreach(['open','in_progress','resolved'] as $st)
                    <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>
                        {{ ucfirst(str_replace('_', ' ', $st)) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="filter-group">
            <label>From</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}">
        </div>

        <div class="filter-group">
            <label>To</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}">
        </div>

        <div style="display:flex; gap:8px; align-items:flex-end;">
            <button type="submit" class="btn-filter">Filter</button>
            <a href="{{ route('tickets.history') }}" class="btn-reset">Reset</a>
        </div>

    </div>
</form>

{{-- Table --}}
<div class="table-card">
    <table class="history-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Generator</th>
                <th>Title</th>
                <th>Severity</th>
                <th>Status</th>
                <th>Source</th>
                <th>Created</th>
                <th>Resolved</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tickets as $ticket)
            <tr>
                <td class="text-muted">{{ $ticket->id }}</td>
                <td class="gen-name">{{ $ticket->generator->name ?? '—' }}</td>
                <td>{{ $ticket->title }}</td>
                <td>
                    <span class="badge badge-{{ $ticket->severity }}">
                        {{ ucfirst($ticket->severity) }}
                    </span>
                </td>
                <td>
                    <span class="badge badge-{{ $ticket->status }}">
                        {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                    </span>
                </td>
                <td>
                    @if($ticket->triggered_automatically)
                        <span class="badge badge-auto">Auto</span>
                    @else
                        <span class="badge badge-manual">Manual</span>
                    @endif
                </td>
                <td class="text-muted">{{ $ticket->created_at->format('Y-m-d H:i') }}</td>
                <td class="text-muted">
                    {{ $ticket->resolved_at ? \Carbon\Carbon::parse($ticket->resolved_at)->format('Y-m-d H:i') : '—' }}
                </td>
            </tr>
            @empty
            <tr class="empty-row">
                <td colspan="8">No tickets found matching your filters.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination-wrap">
        {{ $tickets->links() }}
    </div>
</div>

@endsection