<?php

namespace App\Http\Controllers;

use App\Models\FailurePrediction;
use App\Models\Generator;
use Illuminate\Http\Request;

class ClassifierController extends Controller
{
    public function index(Request $request)
    {
        $query = FailurePrediction::with('generator')
            ->where('predicted_at', '>=', now()->subHours(24)); // ← add this

        if ($request->filled('generator_id')) {
            $query->where('generator_id', $request->generator_id);
        }

        if ($request->filled('failure_only')) {
            $query->where('predicted_failure', 1);
        }

        if ($request->filled('mode')) {
            $query->where($request->mode, 1);
        }

        $predictions = $query->orderBy('predicted_at', 'desc')->simplePaginate(25)->withQueryString();
        $generators  = Generator::orderBy('name')->get();

        // Summary stats per generator
        $stats = FailurePrediction::selectRaw('
            generator_id,
            COUNT(*) as total,
            SUM(predicted_failure) as failures,
            SUM(twf) as twf,
            SUM(hdf) as hdf,
            SUM(pwf) as pwf,
            SUM(osf) as osf,
            SUM(rnf) as rnf,
            AVG(confidence) as avg_confidence
        ')->groupBy('generator_id')->get()->keyBy('generator_id');

        return view('classifier', compact('predictions', 'generators', 'stats'));
    }
}