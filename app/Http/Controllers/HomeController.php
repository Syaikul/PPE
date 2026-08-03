<?php

namespace App\Http\Controllers;

use App\Services\MasterApiService;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $gudangs = MasterApiService::gudang();

        return view('home', compact('gudangs'));
    }
}
