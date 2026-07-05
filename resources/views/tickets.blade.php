@extends('layouts.app')

@section('content')

<style>
    table { width:100%; border-collapse:collapse; background:#fff; border-radius:10px;
            overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.08); font-size:14px; }
    thead tr { background:#1a2535; color:#fff; }
    thead th { padding:14px 20px; text-align:left; font-size:13px; font-weight:600; }
    tbody tr { border-bottom:1px solid #f1f5f9; }
    tbody tr:last-child { border-bottom:none; }
    tbody tr:hover { background:#f8fafc; }
    tbody td { padding:14px 20px; }

    .badge { padding:4px 10px; border-radius:20px; font-size:11px;
             font-weight:700; letter-spacing:.5px; text-transform:uppercase; }
    .badge-critical { background:#fee2e2; color:#991b1b; }
    .badge-high     { background:#ffedd5; color:#9a3412; }
    .badge-medium   { background:#fef3c7; color:#92400e; }
    .badge-low      { background:#d1fae5; color:#065f46; }
    .badge-auto     { background:#e0e7ff; color:#3730a3; }

    .btn-resolve {
        background:#1a2535; color:#fff; border:none; padding:6px 14px;
        border-radius:6px; font-size:12px; cursor:pointer; transition:background .2s;
    }
    .btn-resolve:hover { background:#3b82f6; }

    .empty { text-align:center; padding:48px; color:#94a3b8; font-size:15px; }
</style>

<div class="page-title">🔧 Maintenance Tickets</div>

@if($tickets->isEmpty())
    <div class="empty">No open tickets — all generators are healthy.</div>
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
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tickets as $ticket)
            <tr>
                <td>{{ $ticket->id }}</td>
                <td><strong>{{ $ticket->generator->name }}</strong></td>
                <td>{{ $ticket->title }}</td>
                <td><span class="badge badge-{{ $ticket->severity }}">{{ $ticket->severity }}</span></td>
                <td>
                    @if($ticket->triggered_automatically)
                        <span class="badge badge-auto">Auto</span>
                    @else
                        Manual
                    @endif
                </td>
                <td>{{ \Carbon\Carbon::parse($ticket->created_at)->format('d M Y H:i') }}</td>
                <td>
                    <form method="POST"
                          action="{{ route('tickets.resolve', $ticket->id) }}">
                        @csrf
                        <button class="btn-resolve" type="submit">Resolve</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endif

@endsection