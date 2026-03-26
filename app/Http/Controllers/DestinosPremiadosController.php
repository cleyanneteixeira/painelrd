<?php

namespace App\Http\Controllers;

use App\Models\Raffle;
use App\Models\RaffleWinner;

class DestinosPremiadosController extends Controller
{
    public function index()
    {
        $ativos = Raffle::where('status', 'active')
            ->with(['prizes'])
            ->orderByRaw('draw_date IS NULL ASC, draw_date ASC')
            ->get();

        $encerrados = Raffle::where('status', 'finished')
            ->with(['winnerTicket.user', 'prizes.winnerTicket.user'])
            ->latest()
            ->take(6)
            ->get();

        $ganhadores = RaffleWinner::where('active', true)
            ->latest('draw_date')
            ->take(6)
            ->get();

        return view('destino-premiado.index', compact('ativos', 'encerrados', 'ganhadores'));
    }

    public function show(string $slug)
    {
        $raffle = Raffle::with([
            'prizes.winnerTicket.user',
            'winnerTicket.user',
        ])
            ->where('slug', $slug)
            ->whereIn('status', ['active', 'drawing', 'finished'])
            ->firstOrFail();

        return view('destino-premiado.show', compact('raffle'));
    }
}
