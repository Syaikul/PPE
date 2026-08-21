<?php

namespace App\Http\Controllers;

use App\Services\MasterApiService;
use App\Services\GudangContext;

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
        $user = auth()->user();

        $gudangs = collect($gudangs)->map(function ($gudang) use ($user) {
            $gudang['bisa_akses'] = $user->canAccessGudang((int) $gudang['idgudang']);

            return $gudang;
        })->all();

        return view('home', compact('gudangs'));
    }

    public function enter(int $idgudang)
    {
        $user = auth()->user();

        if (! $user->canAccessGudang($idgudang)) {
            return redirect()->route('home')->with('error', 'Anda tidak punya akses ke gudang ini. Hubungi SuperAdmin jika perlu ditugaskan.');
        }

        GudangContext::activate($idgudang);

        return redirect()->route('dashboard');
    }
}
