<?php

namespace App\Http\Controllers;

use App\Models\Generator;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class GeneratorController extends Controller
{
    /**
     * Display the generator dashboard.
     */
    public function index(): View
    {
        $generators = Generator::all();
        return view('dashboard', ['generators' => $generators]);
    }

    /**
     * Display a specific generator.
     */
    public function show(int $id): View
    {
        $generator = Generator::findOrFail($id);
        return view('generator.show', ['generator' => $generator]);
    }
}
