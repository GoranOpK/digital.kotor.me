<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CompetitionsController extends Controller
{
    public function index()
    {
        // Prikaz liste konkursa
        return view('competitions.index');
    }

    public function show($id)
    {
        // Prikaz detalja konkursa
        return view('competitions.show');
    }

    public function apply(Request $request)
    {
        // Prijava na konkurs
    }
}