<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceTicket;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class TicketController extends Controller
{
    /**
     * Display all maintenance tickets.
     */
    public function index(): View
    {
        $tickets = MaintenanceTicket::all();
        return view('tickets.index', ['tickets' => $tickets]);
    }

    /**
     * Resolve a maintenance ticket.
     */
    public function resolve(int $id): RedirectResponse
    {
        $ticket = MaintenanceTicket::findOrFail($id);
        $ticket->update(['status' => 'resolved']);
        return redirect()->route('tickets.index');
    }

    /**
     * Display ticket history.
     */
    public function history(): View
    {
        $tickets = MaintenanceTicket::orderBy('created_at', 'desc')->get();
        return view('tickets.history', ['tickets' => $tickets]);
    }
}
