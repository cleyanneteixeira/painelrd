@extends('layouts.app')

@section('title', $raffle->title . ' — Destino Premiado')

@section('content')

{{--Hero--}}
<section class="relative h-80 flex items-end overflow-hidden">
    @if($raffle->image)
        <img src="{{ asset('storage/' . $raffle->image) }}" class="absolute inset-0 w-full h-full object-cover" alt="{{ $raffle->title }}">
        <div class="absolute inset-0 bg-gradient-to-t from-black/90 to-black/20"></div>
    @else
        <div class="absolute inset-0 bg-gradient-to-br from-orange-500 to-yellow-500"></div>
    @endif
    <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pb-8 text-white w-full">
        <div class="flex items-center gap-3 mb-3">
            <span class="bg-orange-500 text-white text-xs font-bold px-3 py-1 rounded-full">🏆 Destino Premiado</span>
            @if($raffle->isActive())
                <span class="bg-green-500 text-white text-xs font-bold px-3 py-1 rounded-full animate-pulse">🟢 Ativo</span>
            @elseif($raffle->isFinished())
                <span class="bg-gray-500 text-white text-xs font-bold px-3 py-1 rounded-full">Encerrado</span>
            @endif
        </div>
        <h1 class="text-3xl md:text-4xl font-extrabold mb-2">{{ $raffle->title }}</h1>
        @if($raffle->isActive())
        <p class="text-orange-200 text-sm">
            🎟️ R$ {{ number_format($raffle->ticket_price, 2, ',', '.') }}/cota ·
            @if($raffle->isAutoDrawWhenSoldOut())
                ⚡ Sorteio quando todas as cotas forem vendidas
            @else
                📅 Sorteio em {{ $raffle->draw_date->format('d/m/Y') }}
            @endif
        </p>
        @endif
    </div>
</section>

{{--Contador regressivo (só se tiver data)--}}
@if($raffle->isActive() && $raffle->draw_date)
<div class="bg-gradient-to-r from-orange-600 to-red-600 text-white py-5" x-data="countdown('{{ $raffle->draw_date->toISOString() }}')">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <p class="text-orange-200 text-sm font-medium mb-3 uppercase tracking-wider">⏳ Tempo restante para o sorteio</p>
        <div class="flex justify-center gap-4">
            <template x-if="ended">
                <p class="text-2xl font-bold">🎉 Sorteio em andamento!</p>
            </template>
            <template x-if="!ended">
                <div class="flex gap-3">
                    <div class="bg-white/20 rounded-xl px-5 py-3 min-w-[70px]">
                        <p class="text-3xl font-extrabold" x-text="days"></p>
                        <p class="text-xs text-orange-200 mt-1">dias</p>
                    </div>
                    <div class="bg-white/20 rounded-xl px-5 py-3 min-w-[70px]">
                        <p class="text-3xl font-extrabold" x-text="hours"></p>
                        <p class="text-xs text-orange-200 mt-1">horas</p>
                    </div>
                    <div class="bg-white/20 rounded-xl px-5 py-3 min-w-[70px]">
                        <p class="text-3xl font-extrabold" x-text="minutes"></p>
                        <p class="text-xs text-orange-200 mt-1">min</p>
                    </div>
                    <div class="bg-white/20 rounded-xl px-5 py-3 min-w-[70px]">
                        <p class="text-3xl font-extrabold" x-text="seconds"></p>
                        <p class="text-xs text-orange-200 mt-1">seg</p>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
@elseif($raffle->isActive() && $raffle->isAutoDrawWhenSoldOut())
<div class="bg-gradient-to-r from-green-600 to-teal-600 text-white py-4 text-center">
    <p class="font-bold text-lg">⚡ Sorteio acontece quando todas as {{ $raffle->total_tickets }} cotas forem vendidas!</p>
    <p class="text-green-200 text-sm mt-1">Restam apenas {{ $raffle->available_count }} cotas. Garanta a sua agora!</p>
</div>
@endif

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        {{--Coluna principal--}}
        <div class="lg:col-span-2 space-y-6">

            {{--Prêmios múltiplos--}}
            @if($raffle->prizes->count() > 0)
            <div class="bg-white rounded-2xl shadow p-6">
                <h2 class="font-bold text-xl text-gray-900 mb-5">🏆 Prêmios</h2>
                <div class="space-y-4">
                    @foreach($raffle->prizes as $prize)
                    <div class="flex gap-4 p-4 rounded-xl {{ $loop->first ? 'bg-yellow-50 border border-yellow-200' : ($loop->index === 1 ? 'bg-gray-50 border border-gray-200' : 'bg-orange-50 border border-orange-200') }}">
                        @if($prize->image)
                        <img src="{{ asset('storage/' . $prize->image) }}" class="w-20 h-20 object-cover rounded-xl flex-shrink-0">
                        @else
                        <div class="w-20 h-20 rounded-xl flex-shrink-0 flex items-center justify-center text-4xl {{ $loop->first ? 'bg-yellow-200' : 'bg-gray-200' }}">
                            {{ $loop->first ? '🥇' : ($loop->index === 1 ? '🥈' : '🥉') }}
                        </div>
                        @endif
                        <div>
                            <span class="text-xs font-bold {{ $loop->first ? 'text-yellow-600' : 'text-gray-500' }}">{{ $prize->position_label }}</span>
                            <h3 class="font-bold text-gray-900 mt-0.5">{{ $prize->title }}</h3>
                            @if($prize->description)
                            <p class="text-sm text-gray-500 mt-1">{{ $prize->description }}</p>
                            @endif
                            @if($raffle->isFinished() && $prize->winnerTicket)
                            <p class="text-sm font-bold text-green-600 mt-2">🎉 Ganhador: {{ $prize->winnerTicket->user->name }} — Cota #{{ $prize->winnerTicket->number }}</p>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if($raffle->description)
            <div class="bg-white rounded-2xl shadow p-6">
                <h2 class="font-bold text-lg text-gray-900 mb-3">Sobre esta rifa</h2>
                <p class="text-gray-600 leading-relaxed">{{ $raffle->description }}</p>
            </div>
            @endif

            {{--Progresso das cotas--}}
            <div class="bg-white rounded-2xl shadow p-6">
                <h2 class="font-bold text-lg text-gray-900 mb-4">📊 Cotas</h2>
                <div class="grid grid-cols-3 gap-4 mb-5 text-center">
                    <div class="bg-orange-50 rounded-xl p-4">
                        <p class="text-2xl font-extrabold text-orange-500">{{ $raffle->sold_count }}</p>
                        <p class="text-xs text-gray-500 mt-1">Vendidas</p>
                    </div>
                    <div class="bg-green-50 rounded-xl p-4">
                        <p class="text-2xl font-extrabold text-green-500">{{ $raffle->available_count }}</p>
                        <p class="text-xs text-gray-500 mt-1">Disponíveis</p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-4">
                        <p class="text-2xl font-extrabold text-gray-700">{{ $raffle->total_tickets }}</p>
                        <p class="text-xs text-gray-500 mt-1">Total</p>
                    </div>
                </div>
                <div class="relative w-full bg-gray-200 rounded-full h-5 overflow-hidden">
                    <div class="bg-gradient-to-r from-orange-500 to-yellow-400 h-5 rounded-full transition-all duration-700 flex items-center justify-end pr-2"
                         style="width: {{ min($raffle->progress_percentage, 100) }}%">
                        @if($raffle->progress_percentage > 10)
                        <span class="text-white text-xs font-bold">{{ $raffle->progress_percentage }}%</span>
                        @endif
                    </div>
                </div>
                @if($raffle->progress_percentage <= 10)
                <p class="text-xs text-gray-400 text-right mt-1">{{ $raffle->progress_percentage }}% vendido</p>
                @endif
                @if($raffle->available_count <= 20 && $raffle->isActive())
                <p class="text-center text-red-600 font-bold text-sm mt-3 animate-pulse">🔥 Últimas {{ $raffle->available_count }} cotas!</p>
                @endif
            </div>

            {{--Participantes recentes--}}
            @if($raffle->soldTickets()->count() > 0)
            <div class="bg-white rounded-2xl shadow p-6">
                <h2 class="font-bold text-lg text-gray-900 mb-4">👥 Participantes recentes</h2>
                <div class="space-y-2">
                    @foreach($raffle->soldTickets()->with('user')->latest()->take(10)->get() as $ticket)
                    <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center text-sm font-bold">
                                {{ strtoupper(substr($ticket->user->name, 0, 1)) }}
                            </div>
                            <span class="text-sm text-gray-700">{{ $ticket->user->name }}</span>
                        </div>
                        <span class="text-xs font-bold text-orange-500 bg-orange-50 px-2 py-1 rounded-full">#{{ $ticket->number }}</span>
                    </div>
                    @endforeach
                </div>
                @if($raffle->soldTickets()->count() > 10)
                <p class="text-xs text-gray-400 text-center mt-3">+ {{ $raffle->soldTickets()->count() - 10 }} participantes</p>
                @endif
            </div>
            @endif

        </div>

        {{--Sidebar de compra--}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl shadow-lg p-6 sticky top-4">

                @if($raffle->isActive())

                {{--Tabela de desconto por quantidade--}}
                <div class="bg-orange-50 rounded-xl p-4 mb-5">
                    <p class="text-xs font-bold text-orange-700 uppercase tracking-wider mb-3">💰 Quanto mais cotas, mais barato!</p>
                    <div class="space-y-1.5">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">1 cota</span>
                            <span class="font-bold text-gray-900">R$ {{ number_format($raffle->ticket_price, 2, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">5 cotas</span>
                            <span class="font-bold text-green-600">R$ {{ number_format($raffle->ticket_price * 5 * 0.95, 2, ',', '.') }} <span class="text-xs font-normal text-green-500">(-5%)</span></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">10 cotas</span>
                            <span class="font-bold text-green-600">R$ {{ number_format($raffle->ticket_price * 10 * 0.90, 2, ',', '.') }} <span class="text-xs font-normal text-green-500">(-10%)</span></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">20+ cotas</span>
                            <span class="font-bold text-green-600">R$ {{ number_format($raffle->ticket_price * 20 * 0.85, 2, ',', '.') }} <span class="text-xs font-normal text-green-500">(-15%)</span></span>
                        </div>
                    </div>
                </div>

                @auth
                <form method="POST" action="{{ route('pagamento.destino.processar', $raffle) }}" x-data="{ qty: 1, price: {{ $raffle->ticket_price }} }">
                    @csrf
                    <label class="block text-sm font-medium text-gray-700 mb-2">Quantidade de cotas</label>
                    <div class="flex items-center gap-2 mb-4">
                        <button type="button" @click="qty = Math.max(1, qty - 1)"
                            class="w-10 h-10 rounded-xl bg-gray-100 hover:bg-gray-200 font-bold text-lg transition-colors">−</button>
                        <input type="number" name="quantidade" x-model="qty" min="1" max="{{ $raffle->available_count }}"
                            class="flex-1 text-center border border-gray-300 rounded-xl py-2 font-bold text-lg focus:outline-none focus:ring-2 focus:ring-orange-400">
                        <button type="button" @click="qty = Math.min({{ $raffle->available_count }}, qty + 1)"
                            class="w-10 h-10 rounded-xl bg-gray-100 hover:bg-gray-200 font-bold text-lg transition-colors">+</button>
                    </div>

                    <div class="bg-gray-50 rounded-xl p-3 mb-4 text-center">
                        <p class="text-sm text-gray-500">Total estimado</p>
                        <p class="text-2xl font-extrabold text-orange-500" x-text="'R$ ' + (qty * price).toLocaleString('pt-BR', {minimumFractionDigits:2})"></p>
                        <p class="text-xs text-gray-400 mt-0.5">* Desconto aplicado no checkout</p>
                    </div>

                    <button type="submit"
                        class="w-full bg-gradient-to-r from-orange-500 to-yellow-500 hover:from-orange-600 hover:to-yellow-600 text-white py-4 rounded-xl font-extrabold text-lg transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                        🎟️ Garantir minhas cotas →
                    </button>
                </form>
                @else
                <div class="text-center py-4">
                    <p class="text-gray-500 text-sm mb-4">Faça login para comprar suas cotas</p>
                    <a href="{{ route('login') }}" class="block w-full bg-orange-500 hover:bg-orange-600 text-white py-3.5 rounded-xl font-bold transition-colors">
                        Entrar para participar →
                    </a>
                    <a href="{{ route('cadastro') }}" class="block w-full border border-gray-300 text-gray-600 hover:bg-gray-50 py-3 rounded-xl font-medium mt-2 transition-colors text-sm">
                        Criar conta grátis
                    </a>
                </div>
                @endauth

                <div class="mt-4 text-center space-y-1.5">
                    <p class="text-xs text-gray-400">🔒 Pagamento 100% seguro via Mercado Pago</p>
                    <p class="text-xs text-gray-400">PIX, Cartão ou Boleto</p>
                </div>

                @elseif($raffle->isFinished())
                <div class="text-center py-6">
                    <div class="text-5xl mb-3">🎉</div>
                    <p class="font-bold text-gray-900 text-lg">Sorteio encerrado!</p>
                    <p class="text-gray-500 text-sm mt-1">Obrigado a todos que participaram.</p>
                </div>
                @endif

            </div>
        </div>

    </div>
</div>

{{--Script contador regressivo--}}
<script>
function countdown(targetDate) {
    return {
        days: '00', hours: '00', minutes: '00', seconds: '00',
        ended: false,
        init() {
            this.update();
            setInterval(() => this.update(), 1000);
        },
        update() {
            const diff = new Date(targetDate) - new Date();
            if (diff <= 0) { this.ended = true; return; }
            const d = Math.floor(diff / 86400000);
            const h = Math.floor((diff % 86400000) / 3600000);
            const m = Math.floor((diff % 3600000) / 60000);
            const s = Math.floor((diff % 60000) / 1000);
            this.days    = String(d).padStart(2, '0');
            this.hours   = String(h).padStart(2, '0');
            this.minutes = String(m).padStart(2, '0');
            this.seconds = String(s).padStart(2, '0');
        }
    }
}
</script>
@endsection
