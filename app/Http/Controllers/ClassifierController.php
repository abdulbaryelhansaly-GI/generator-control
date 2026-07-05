<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ClassifierController extends Controller
{
    /**
     * Display the classifier page.
     */
    public function index(): View
    {
        return view('classifier.index');
    }
}
