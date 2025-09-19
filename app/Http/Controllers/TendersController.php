<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TendersController extends Controller
{
    public function index()
    {
        // Prikaz liste tendera
        return view('tenders.index');
    }

    public function show($id)
    {
        // Prikaz detalja tendera
        return view('tenders.show');
    }

    public function purchase(Request $request)
    {
        // Otkup tenderske dokumentacije
    }
}