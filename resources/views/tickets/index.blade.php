@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1>Maintenance Tickets</h1>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Generator ID</th>
                        <th>Title</th>
                        <th>Severity</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tickets as $ticket)
                    <tr>
                        <td>{{ $ticket->id }}</td>
                        <td>{{ $ticket->generator_id }}</td>
                        <td>{{ $ticket->title }}</td>
                        <td>{{ $ticket->severity }}</td>
                        <td>{{ $ticket->status }}</td>
                        <td>
                            @if($ticket->status !== 'resolved')
                            <form method="POST" action="{{ route('tickets.resolve', $ticket->id) }}" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success">Resolve</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
