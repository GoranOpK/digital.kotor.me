<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PaymentsController extends Controller
{
    public function index()
    {
        // Prikaz forme za plaćanje
        return view('payments.index');
    }

    public function pay(Request $request)
    {
        // Logika za plaćanje
    }
}