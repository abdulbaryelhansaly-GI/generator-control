<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceTicket;
use Illuminate\Http\Request;
use App\Models\Generator;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    // List all open tickets
    public function index()
    {
        $tickets = MaintenanceTicket::with('generator')
            ->where('status', '!=', 'resolved')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('tickets', compact('tickets'));
    }

    // Mark a ticket as resolved
    public function resolve($id)
    {
        $ticket = MaintenanceTicket::findOrFail($id);
        $ticket->update([
            'status'      => 'resolved',
            'resolved_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Ticket resolved.');
    }
    // Maintenance history with filters
public function history(Request $request)
{
    $query = MaintenanceTicket::with('generator');

    // Filter by generator
    if ($request->filled('generator_id')) {
        $query->where('generator_id', $request->generator_id);
    }

    // Filter by severity
    if ($request->filled('severity')) {
        $query->where('severity', $request->severity);
    }

    // Filter by status
    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    // Filter by date range
    if ($request->filled('date_from')) {
        $query->whereDate('created_at', '>=', $request->date_from);
    }

    if ($request->filled('date_to')) {
        $query->whereDate('created_at', '<=', $request->date_to);
    }

    $tickets    = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();
    $generators = Generator::orderBy('name')->get();

    return view('history', compact('tickets', 'generators'));
}
}