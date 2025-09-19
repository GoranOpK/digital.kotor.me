<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    // Prikaz forme za prijavu na konkurs
    public function create($competition_id)
    {
        //
    }

    // Snimi prijavu
    public function store(Request $request, $competition_id)
    {
        //
    }

    // Prikaz detalja prijave
    public function show($id)
    {
        //
    }

    // Upload dokumenata
    public function uploadDocument(Request $request, $application_id)
    {
        //
    }

    // Prikaz statusa prijave
    public function status($id)
    {
        //
    }
}