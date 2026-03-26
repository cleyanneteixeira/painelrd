<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\Experience;
use App\Models\Hotel;
use App\Models\Raffle;
use App\Models\Setting;

class HomeController extends Controller
{
    public function index()
    {
        $destinos = Destination::latest()->take(4)->get();

        $experiencias = Experience::with('city')->latest()->take(6)->get();

        $hoteis = Hotel::with(['city', 'rooms'])->latest()->take(6)->get();

        $destinoPremiado = Raffle::where('status', 'active')
            ->orderBy('draw_date')
            ->first();

        //Rifa em destaque: manual ou automática
        $rifaDestaqueId = \App\Models\Setting::get('rifa_destaque_id');
        if ($rifaDestaqueId) {
            $rifaAtiva = Raffle::where('status', 'active')
                ->where('id', $rifaDestaqueId)
                ->with('prizes')
                ->first();
        }
        if (empty($rifaAtiva)) {
            $rifaAtiva = Raffle::where('status', 'active')
                ->with('prizes')
                ->orderByRaw('draw_date IS NULL ASC, draw_date ASC')
                ->first();
        }

        $totalDestinos     = Destination::count();
        $totalHoteis       = Hotel::count();
        $totalExperiencias = Experience::count();

        $heroBanner = Setting::randomHeroImage();

        return view('home', compact(
            'destinos',
            'experiencias',
            'hoteis',
            'destinoPremiado',
            'rifaAtiva',
            'totalDestinos',
            'totalHoteis',
            'totalExperiencias',
            'heroBanner'
        ));
    }
}
