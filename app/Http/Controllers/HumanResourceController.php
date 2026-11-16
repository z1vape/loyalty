<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HumanResourceController extends Controller
{
    /**
     * Display the Human Resources area, redirecting directly to Daftar Kehadiran.
     */
    public function index(Request $request)
    {
        return redirect()->route('commercial.hr.dashboard');
    }
}


