<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RegisteredUserController extends Controller
{
    /**
     * Dead Breeze registration. Hard-disabled so route order cannot revive identity-less signup.
     */
    public function create()
    {
        abort(404);
    }

    /**
     * Dead Breeze registration. Hard-disabled so route order cannot revive identity-less signup.
     */
    public function store(Request $request)
    {
        abort(404);
    }
}
