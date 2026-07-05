@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1>Generator Dashboard</h1>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Location</th>
                        <th>Model</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($generators as $generator)
                    <tr>
                        <td>{{ $generator->id }}</td>
                        <td>{{ $generator->name }}</td>
                        <td>{{ $generator->location }}</td>
                        <td>{{ $generator->model }}</td>
                        <td>{{ $generator->status }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
