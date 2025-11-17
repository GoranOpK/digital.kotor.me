<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReportController extends Controller
{
    // Prikaz forme za izvještaj
    public function create($application_id)
    {
        //
    }

    // Snimi izvještaj
    public function store(Request $request, $application_id)
    {
        //
    }

    // Upload dokaza realizacije
    public function upload(Request $request, $report_id)
    {
        //
    }

    // Ocjena realizacije
    public function evaluate(Request $request, $report_id)
    {
        //
    }
}