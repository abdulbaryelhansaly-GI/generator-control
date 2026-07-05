<?php

namespace App\Http\Controllers;

use App\Models\Generator;
use Illuminate\Http\Response;

class ExportController extends Controller
{
    /**
     * Export generators as CSV.
     */
    public function csv(): Response
    {
        $generators = Generator::all();
        $csv = "ID,Name,Location,Model,Status\n";
        foreach ($generators as $generator) {
            $csv .= "{$generator->id},{$generator->name},{$generator->location},{$generator->model},{$generator->status}\n";
        }
        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="generators.csv"',
        ]);
    }

    /**
     * Export generators as PDF.
     */
    public function pdf(): Response
    {
        $generators = Generator::all();
        $pdf = "Generator Report\n";
        $pdf .= "=" . str_repeat("=", 40) . "\n\n";
        foreach ($generators as $generator) {
            $pdf .= "ID: {$generator->id}\n";
            $pdf .= "Name: {$generator->name}\n";
            $pdf .= "Location: {$generator->location}\n";
            $pdf .= "Model: {$generator->model}\n";
            $pdf .= "Status: {$generator->status}\n";
            $pdf .= "-" . str_repeat("-", 40) . "\n\n";
        }
        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="report.pdf"',
        ]);
    }
}
