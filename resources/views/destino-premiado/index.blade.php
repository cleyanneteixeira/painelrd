@extends('layouts.app')

@section('title', 'Destino Premiado — Ganhe uma viagem pelo Maranhão')
@section('seo_description', 'Participe das rifas do Reservar Destinos e ganhe pacotes de viagem, hospedagens e experiências incríveis no Maranhão!')

@section('content')

{{--Hero--}}
<section class="bg-gradient-to-br from-orange-500 via-orange-600 to-yellow-500 text-white py-16 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10" style="background-image: url('data:image/svg+xml,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 100 100\"><text y=\".9em\" font-size=\"90\">🏆</text></svg>'); background-size: 120px; background-repeat: repeat;"></div>
    <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="inline-flex items-center gap-2 bg-white/20 rounded-full px-4 py-1.5 text-sm font-semibold mb-4">
            🏆 Destino Premiado
        </div>
        <h1 class="text-4xl md:text-5xl font-extrabold mb-4 leading-tight">
            Ganhe uma experiência<br>inesquecível no Maranhão
        </h1>
        <p class="text-orange-100 text-lg max-w-xl mx-auto mb-6">
            Compre sua cota, torça pelo seu número e realize o sonho de conhecer os melhores destinos e desfrutar das melhores experiências do Maranhão.
        </p>
        @if($ativos->isNotEmpty())
        <a href="#rifas-ativas" class="inline-block bg-white text-orange-600 hover:bg-orange-50 px-8 py-3.5 rounded-xl font-extrabold text-lg transition-colors shadow-lg">
            🎟️ Ver rifas ativas
        </a>
        @endif
    </div>
</section>

{{--Como funciona--}}
<section class="bg-white border-b">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h2 class="text-center text-xl font-bold text-gray-800 mb-8">Como funciona?</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-6 text-center">
            <div><div class="text-3xl mb-2">🎟️</div><p class="font-semibold text-gray-800 text-sm">Escolha sua cota</p><p class="text-xs text-gray-500 mt-1">Compre quantas quiser</p></div>
            <div><div class="text-3xl mb-2">💳</div><p class="font-semibold text-gray-800 text-sm">Pague com PIX</p><p class="text-xs text-gray-500 mt-1">Rápido e seguro</p></div>
            <div><div class="text-3xl mb-2">🎯</div><p class="font-semibold text-gray-800 text-sm">Aguarde o sorteio</p><p class="text-xs text-gray-500 mt-1">Data na promoção</p></div>
            <div><div class="text-3xl mb-2">✈️</div><p class="font-semibold text-gray-800 text-sm">Viaje e aproveite!</p><p class="text-xs text-gray-500 mt-1">Ganhador recebe voucher</p></div>
        </div>
    </div>
</section>

{{--Rifas ativas--}}
<section id="rifas-ativas" class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    @if($ativos->isEmpty() && $encerrados->isEmpty())
        <div class="text-center py-20 text-gray-400">
            <p class="text-5xl mb-4">🏆</p>
            <p class="text-lg font-medium">Nenhum Destino Premiado disponível no momento.</p>
            <p class="text-sm mt-2">Em breve novas promoções. Fique de olho!</p>
        </div>
    @endif

    @if($ativos->isNotEmpty())
        <h2 class="text-2xl font-bold text-gray-900 mb-6">🔥 Rifas ativas</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-12">
            @foreach($ativos as $raffle)
            <a href="{{ route('destino-premiado.show', $raffle->slug) }}"
               class="group bg-white rounded-2xl shadow hover:shadow-xl transition-all overflow-hidden border-2 border-orange-200 hover:border-orange-400">
                <div class="relative h-52 bg-gradient-to-br from-orange-400 to-yellow-400 overflow-hidden">
                    @if($raffle->image)
                        <img src="{{ asset('storage/' . $raffle->image) }}" alt="{{ $raffle->title }}"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    @else
                        <div class="flex items-center justify-center h-full text-white text-6xl">🏆</div>
                    @endif
                    <div class="absolute top-3 left-3 flex gap-2 flex-wrap">
                        <span class="bg-green-500 text-white text-xs font-bold px-3 py-1 rounded-full animate-pulse">🟢 Ativo</span>
                        @if($raffle->isAutoDrawWhenSoldOut())
                            <span class="bg-blue-600 text-white text-xs font-bold px-3 py-1 rounded-full">⚡ Auto-sorteio</span>
                        @endif
                    </div>
                    <div class="absolute top-3 right-3">
                        <span class="bg-white/90 text-orange-600 text-xs font-bold px-3 py-1 rounded-full">
                            R$ {{ number_format($raffle->ticket_price, 2, ',', '.') }}/cota
                        </span>
                    </div>
                </div>
                <div class="p-5">
                    <h3 class="font-extrabold text-gray-900 text-lg mb-1">{{ $raffle->title }}</h3>

                    {{--Prêmios resumidos--}}
                    @if($raffle->prizes->count() > 0)
                    <div class="flex gap-1 flex-wrap mb-3">
                        @foreach($raffle->prizes->take(3) as $prize)
                        <span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full font-medium">
                            {{ $prize->position === 1 ? '🥇' : ($prize->position === 2 ? '🥈' : '🥉') }} {{ Str::limit($prize->title, 25) }}
                        </span>
                        @endforeach
                    </div>
                    @endif

                    {{--Progresso--}}
                    <div class="mb-3">
                        <div class="flex justify-between text-xs text-gray-500 mb-1">
                            <span>{{ $raffle->sold_count }} cotas vendidas</span>
                            <span>{{ $raffle->available_count }} disponíveis</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="bg-gradient-to-r from-orange-500 to-yellow-400 h-2.5 rounded-full"
                                 style="width: {{ min($raffle->progress_percentage, 100) }}%"></div>
                        </div>
                        @if($raffle->available_count <= 20)
                        <p class="text-xs text-red-600 font-bold mt-1 animate-pulse">🔥 Últimas {{ $raffle->available_count }} cotas!</p>
                        @endif
                    </div>

                    {{--Data/condição do sorteio--}}
                    <p class="text-xs text-gray-500">
                        @if($raffle->isAutoDrawWhenSoldOut())
                            ⚡ Sorteio quando todas as cotas forem vendidas
                        @else
                            📅 Sorteio em {{ $raffle->draw_date->format('d/m/Y') }}
                        @endif
                    </p>

                    <div class="mt-4 bg-orange-500 group-hover:bg-orange-600 text-white text-center py-2.5 rounded-xl font-bold text-sm transition-colors">
                        🎟️ Garantir minha cota
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    @endif

    {{--Rifas encerradas--}}
    @if($encerrados->isNotEmpty())
    <h2 class="text-2xl font-bold text-gray-900 mb-6">✅ Rifas encerradas</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-12">
        @foreach($encerrados as $raffle)
        <a href="{{ route('destino-premiado.show', $raffle->slug) }}"
           class="group bg-white rounded-2xl shadow hover:shadow-lg transition-all overflow-hidden opacity-80 hover:opacity-100">
            <div class="relative h-36 bg-gray-200 overflow-hidden">
                @if($raffle->image)
                    <img src="{{ asset('storage/' . $raffle->image) }}" alt="{{ $raffle->title }}"
                         class="w-full h-full object-cover grayscale group-hover:grayscale-0 transition-all duration-500">
                @else
                    <div class="flex items-center justify-center h-full text-4xl text-gray-400">🏆</div>
                @endif
                <span class="absolute top-3 left-3 bg-gray-600 text-white text-xs font-bold px-3 py-1 rounded-full">Encerrado</span>
            </div>
            <div class="p-4">
                <h3 class="font-bold text-gray-800 text-sm mb-1">{{ $raffle->title }}</h3>
                @if($raffle->winnerTicket)
                <p class="text-xs text-green-600 font-semibold">🎉 Ganhador: {{ $raffle->winnerTicket->user->name }}</p>
                @endif
            </div>
        </a>
        @endforeach
    </div>
    @endif

</section>

{{--Ganhadores anteriores / depoimentos--}}
@if($ganhadores->isNotEmpty())
<section class="bg-gradient-to-br from-orange-50 to-yellow-50 border-t border-orange-100 py-14">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-extrabold text-gray-900 mb-2 text-center">🌟 Ganhadores que já viajaram</h2>
        <p class="text-center text-gray-500 mb-10">Pessoas reais que compraram suas cotas e realizaram o sonho de conhecer o Maranhão.</p>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
            @foreach($ganhadores as $ganhador)
            <div class="bg-white rounded-2xl shadow p-6 flex flex-col">
                <div class="flex items-center gap-4 mb-4">
                    @if($ganhador->photo_url)
                        <img src="{{ $ganhador->photo_url }}" class="w-14 h-14 rounded-full object-cover">
                    @else
                        <div class="w-14 h-14 rounded-full bg-orange-100 text-orange-500 flex items-center justify-center text-2xl font-bold flex-shrink-0">
                            {{ strtoupper(substr($ganhador->name, 0, 1)) }}
                        </div>
                    @endif
                    <div>
                        <p class="font-bold text-gray-900 text-sm">{{ $ganhador->name }}</p>
                        @if($ganhador->city)
                        <p class="text-xs text-gray-400">📍 {{ $ganhador->city }}</p>
                        @endif
                        <p class="text-xs text-orange-500 font-medium mt-0.5">{{ $ganhador->draw_date->format('m/Y') }}</p>
                    </div>
                </div>
                @if($ganhador->testimonial)
                <p class="text-gray-600 text-sm leading-relaxed italic flex-1">"{{ $ganhador->testimonial }}"</p>
                @endif
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <p class="text-xs font-semibold text-orange-600">🏆 {{ $ganhador->prize }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{--CTA final--}}
@if($ativos->isNotEmpty())
<section class="bg-gradient-to-r from-orange-500 to-yellow-500 py-12 text-white text-center">
    <h2 class="text-2xl font-extrabold mb-2">Não perca a próxima chance!</h2>
    <p class="text-orange-100 mb-6">Cotas a partir de R$ {{ number_format($ativos->min('ticket_price'), 2, ',', '.') }}. Quanto mais cotas, mais chances de ganhar.</p>
    <a href="#rifas-ativas" class="inline-block bg-white text-orange-600 hover:bg-orange-50 px-8 py-3.5 rounded-xl font-extrabold text-lg transition-colors shadow-lg">
        🎟️ Comprar agora
    </a>
</section>
@endif

@endsection
