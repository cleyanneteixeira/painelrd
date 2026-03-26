<?php

namespace App\Http\Controllers\Pagamento;

use App\Http\Controllers\Controller;
use App\Models\Raffle;
use App\Models\RaffleTicket;
use App\Services\MercadoPagoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DestinoController extends Controller
{
    public function __construct(private MercadoPagoService $mp) {}

    public function processar(Request $request, Raffle $raffle): RedirectResponse
    {
        if (! $raffle->isActive()) {
            return back()->withErrors(['cota' => 'Este sorteio não está mais ativo.']);
        }

        $request->validate([
            'quantidade' => ['required', 'integer', 'min:1', 'max:10'],
        ], [
            'quantidade.required' => 'Informe a quantidade de cotas.',
            'quantidade.min'      => 'Mínimo 1 cota.',
            'quantidade.max'      => 'Máximo 10 cotas por vez.',
        ]);

        $quantidade = (int) $request->quantidade;
        $user       = Auth::user();

        //Verificar disponibilidade
        if ($raffle->available_count < $quantidade) {
            return back()->withErrors(['cota' => "Só restam {$raffle->available_count} cotas disponíveis."]);
        }

        //Gerar números únicos
        $numerosUsados = $raffle->tickets()->pluck('number')->toArray();
        $numeros       = [];
        $tentativas    = 0;

        while (count($numeros) < $quantidade && $tentativas < 1000) {
            $num = rand(1, $raffle->total_tickets);
            if (! in_array($num, $numerosUsados) && ! in_array($num, $numeros)) {
                $numeros[] = $num;
            }
            $tentativas++;
        }

        if (count($numeros) < $quantidade) {
            return back()->withErrors(['cota' => 'Não foi possível reservar as cotas. Tente novamente.']);
        }

        //Criar cotas como pending
        $tickets = [];
        foreach ($numeros as $numero) {
            $tickets[] = RaffleTicket::create([
                'raffle_id'      => $raffle->id,
                'user_id'        => $user->id,
                'number'         => $numero,
                'payment_status' => 'pending',
            ]);
        }

        $total      = $raffle->ticket_price * $quantidade;
        $ticketIds  = collect($tickets)->pluck('id')->join(',');

        //Criar preferência no Mercado Pago
        $preferencia = $this->mp->criarPreferencia([
            'id'            => 'destino-' . $ticketIds,
            'titulo'        => "{$quantidade}x cota(s) — {$raffle->title}",
            'valor'         => $total,
            'nome_cliente'  => $user->name,
            'email_cliente' => $user->email,
            'referencia'    => 'destino-' . $ticketIds,
            'url_sucesso'   => route('pagamento.destino.sucesso', ['raffle' => $raffle, 'tickets' => $ticketIds]),
            'url_falha'     => route('pagamento.destino.falha', ['raffle' => $raffle, 'tickets' => $ticketIds]),
            'url_pendente'  => route('pagamento.destino.pendente', ['raffle' => $raffle, 'tickets' => $ticketIds]),
        ]);

        if (! $preferencia) {
            //Cancelar tickets criados
            RaffleTicket::whereIn('id', collect($tickets)->pluck('id'))->delete();
            return back()->withErrors(['cota' => 'Erro ao iniciar pagamento. Tente novamente.']);
        }

        //Salvar preference_id nos tickets
        RaffleTicket::whereIn('id', collect($tickets)->pluck('id'))
            ->update(['mp_preference_id' => $preferencia['id']]);

        return redirect($preferencia['init_point']);
    }

    public function sucesso(Request $request, Raffle $raffle): View
    {
        $ticketIds = explode(',', $request->tickets ?? '');

        if ($request->filled('payment_id')) {
            $pagamento = $this->mp->consultarPagamento($request->payment_id);

            if ($pagamento && $pagamento['status'] === 'approved') {
                RaffleTicket::whereIn('id', $ticketIds)
                    ->update([
                        'payment_status' => 'paid',
                        'transaction_id' => $request->payment_id,
                    ]);
            }
        }

        $tickets = RaffleTicket::whereIn('id', $ticketIds)->get();

        return view('pagamento.sucesso', ['tipo' => 'destino', 'item' => $raffle, 'tickets' => $tickets]);
    }

    public function falha(Request $request, Raffle $raffle): View
    {
        $ticketIds = explode(',', $request->tickets ?? '');
        RaffleTicket::whereIn('id', $ticketIds)->delete();

        return view('pagamento.falha', ['tipo' => 'destino', 'item' => $raffle]);
    }

    public function pendente(Request $request, Raffle $raffle): View
    {
        $ticketIds = explode(',', $request->tickets ?? '');
        $tickets   = RaffleTicket::whereIn('id', $ticketIds)->get();

        return view('pagamento.pendente', ['tipo' => 'destino', 'item' => $raffle, 'tickets' => $tickets]);
    }
}
