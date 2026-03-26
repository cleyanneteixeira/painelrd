@extends('layouts.app')

@section('title', 'Reserve Hotéis e Experiências no Maranhão')

@section('seo_description', 'Reserve hotéis, pousadas e experiências no Maranhão com facilidade e segurança. Lençóis Maranhenses, São Luís, Barreirinhas e muito mais. Seu próximo destino começa aqui.')
@section('seo_keywords', 'reservar destinos, hotéis maranhão, lençóis maranhenses, turismo maranhão, são luís, barreirinhas, pousada maranhão')


@section('content')

{{--Hero--}}
<section class="relative h-[580px] flex items-center justify-center text-white overflow-hidden">
    <img
        src="{{ $heroBanner }}"
        class="absolute inset-0 w-full h-full object-cover"
        alt="Lençóis Maranhenses"
    >
    <div class="absolute inset-0 bg-gradient-to-b from-black/60 via-black/40 to-black/70"></div>

    <div class="relative text-center max-w-3xl px-4 mx-auto">
        <p class="text-orange-400 font-semibold text-sm uppercase tracking-widest mb-3">Descubra o Maranhão</p>
        <h1 class="text-4xl md:text-6xl font-extrabold mb-5 leading-tight">
            Aventuras que ficam<br>na memória para sempre
        </h1>
        <p class="mb-8 text-lg text-gray-200">
            Dunas, história, cultura e natureza. Tudo em um só lugar.
        </p>

        {{--Barra de busca--}}
        <form action="{{ route('hoteis') }}" method="GET" class="bg-white rounded-2xl shadow-2xl p-3 flex flex-col sm:flex-row gap-3 max-w-xl mx-auto">
            <input
                type="text"
                name="busca"
                class="flex-1 border border-gray-200 rounded-xl px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400"
                placeholder="Para onde você quer viajar?"
            >
            <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-3 rounded-xl font-semibold text-sm transition-colors whitespace-nowrap">
                Buscar →
            </button>
        </form>
    </div>
</section>

{{--Destaques rápidos--}}
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-6 relative z-10">
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
            <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center text-2xl flex-shrink-0">🏖️</div>
            <div>
                <p class="font-bold text-gray-800">{{ $totalDestinos }} Destinos</p>
                <p class="text-sm text-gray-500">Para explorar</p>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center text-2xl flex-shrink-0">🏨</div>
            <div>
                <p class="font-bold text-gray-800">{{ $totalHoteis }} Hotéis</p>
                <p class="text-sm text-gray-500">e pousadas</p>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center text-2xl flex-shrink-0">🎒</div>
            <div>
                <p class="font-bold text-gray-800">{{ $totalExperiencias }} Experiências</p>
                <p class="text-sm text-gray-500">Únicas e inesquecíveis</p>
            </div>
        </div>
    </div>
</section>

{{--Destinos populares--}}
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-3xl font-bold text-gray-900">Destinos populares</h2>
            <p class="text-gray-500 mt-1">Os lugares mais visitados do Maranhão</p>
        </div>
        <a href="{{ route('destinos') }}" class="text-orange-500 hover:text-orange-600 font-medium text-sm hidden sm:block">Ver todos →</a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        @foreach($destinos as $destino)
        <a href="{{ route('destinos.show', $destino->slug) }}" class="group block rounded-2xl overflow-hidden shadow hover:shadow-xl transition-shadow bg-white">
            <div class="relative h-44 overflow-hidden bg-gradient-to-br from-orange-400 to-orange-600">
                @if($destino->image_url)
                <img src="{{ $destino->image_url }}" alt="{{ $destino->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                @else
                <div class="w-full h-full flex items-center justify-center text-white text-5xl opacity-50">🏖️</div>
                @endif
                <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
                <div class="absolute bottom-3 left-3 text-white font-bold text-lg">{{ $destino->name }}</div>
            </div>
            <div class="p-4">
                <p class="text-sm text-gray-500 line-clamp-2">{{ $destino->description }}</p>
                <p class="text-orange-500 text-sm font-medium mt-2 group-hover:underline">Explorar →</p>
            </div>
        </a>
        @endforeach
    </div>
</section>

{{--Experiências em destaque--}}
<section class="bg-orange-50 py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-3xl font-bold text-gray-900">Experiências incríveis</h2>
                <p class="text-gray-500 mt-1">Vivências que vão além do turismo comum</p>
            </div>
            <a href="{{ route('experiencias') }}" class="text-orange-500 hover:text-orange-600 font-medium text-sm hidden sm:block">Ver todas →</a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($experiencias as $exp)
            <div class="bg-white rounded-2xl shadow p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-start justify-between mb-3">
                    <span class="text-3xl">{{ ['🏖️','🚤','🎭','🌿','🦅','🍽️'][($loop->index % 6)] }}</span>
                    <span class="bg-orange-100 text-orange-700 text-xs font-bold px-3 py-1 rounded-full">
                        R$ {{ number_format($exp->price, 2, ',', '.') }}
                    </span>
                </div>
                <h3 class="font-bold text-gray-900 mb-1">{{ $exp->name }}</h3>
                <p class="text-sm text-gray-500 mb-3 line-clamp-2">{{ $exp->description }}</p>
                <div class="flex items-center justify-between text-xs text-gray-400">
                    <span>📍 {{ $exp->city->name }}</span>
                    @if($exp->duration)
                    <span>⏱ {{ intdiv($exp->duration, 60) }}h{{ $exp->duration % 60 ? ($exp->duration % 60).'min' : '' }}</span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        <div class="text-center mt-8 sm:hidden">
            <a href="{{ route('experiencias') }}" class="text-orange-500 font-medium">Ver todas as experiências →</a>
        </div>
    </div>
</section>

{{--Hotéis em destaque--}}
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-3xl font-bold text-gray-900">Onde se hospedar</h2>
            <p class="text-gray-500 mt-1">Conforto e aconchego em cada destino</p>
        </div>
        <a href="{{ route('hoteis') }}" class="text-orange-500 hover:text-orange-600 font-medium text-sm hidden sm:block">Ver todos →</a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach($hoteis as $hotel)
        <div class="bg-white rounded-2xl shadow hover:shadow-lg transition-shadow overflow-hidden">
            <div class="relative h-44 bg-gradient-to-br from-blue-100 to-blue-200">
                @if($hotel->image_url)
                <img src="{{ $hotel->image_url }}" alt="{{ $hotel->name }}" class="w-full h-full object-cover">
                @else
                <div class="w-full h-full flex items-center justify-center text-blue-300 text-5xl">🏨</div>
                @endif
            </div>
            <div class="p-5">
                <h3 class="font-bold text-gray-900 text-lg mb-1">{{ $hotel->name }}</h3>
                <p class="text-sm text-gray-500 mb-3 line-clamp-2">{{ $hotel->description }}</p>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-400">📍 {{ $hotel->city->name }}</span>
                    @if($hotel->rooms->count())
                    <span class="text-orange-600 font-bold text-sm">
                        a partir de R$ {{ number_format($hotel->rooms->min('price'), 2, ',', '.') }}
                    </span>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
</section>

{{--Destino Premiado Destaque--}}
@if(isset($rifaAtiva) && $rifaAtiva)
<section class="bg-gradient-to-r from-orange-500 via-orange-600 to-yellow-500 py-12 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10" style="background-image: url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ctext y='.9em' font-size='90'%3E%F0%9F%8F%86%3C/text%3E%3C/svg%3E&quot;); background-size: 80px; background-repeat: repeat;"></div>
    <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col lg:flex-row items-center gap-8">
            @if($rifaAtiva->image)
            <div class="w-full lg:w-72 h-48 lg:h-60 rounded-2xl overflow-hidden flex-shrink-0 shadow-2xl">
                <img src="{{ asset('storage/' . $rifaAtiva->image) }}" class="w-full h-full object-cover">
            </div>
            @endif
            <div class="flex-1 text-white text-center lg:text-left">
                <div class="inline-flex items-center gap-2 bg-white/20 rounded-full px-4 py-1.5 text-sm font-semibold mb-3">
                    🏆 Destino Premiado — Rifa Ativa!
                </div>
                <h2 class="text-3xl font-extrabold mb-2">{{ $rifaAtiva->title }}</h2>

                {{--Prêmios--}}
                @if($rifaAtiva->prizes->count() > 0)
                <div class="flex gap-2 flex-wrap justify-center lg:justify-start mb-4">
                    @foreach($rifaAtiva->prizes->take(3) as $prize)
                    <span class="bg-white/20 text-white text-sm px-3 py-1 rounded-full font-medium">
                        {{ $prize->position === 1 ? '🥇' : ($prize->position === 2 ? '🥈' : '🥉') }} {{ Str::limit($prize->title, 30) }}
                    </span>
                    @endforeach
                </div>
                @endif

                {{--Barra de progresso--}}
                <div class="mb-4">
                    <div class="flex justify-between text-sm text-orange-200 mb-1">
                        <span>{{ $rifaAtiva->sold_count }} cotas vendidas</span>
                        <span>{{ $rifaAtiva->progress_percentage }}% completo</span>
                    </div>
                    <div class="w-full bg-white/30 rounded-full h-3">
                        <div class="bg-white h-3 rounded-full transition-all" style="width: {{ min($rifaAtiva->progress_percentage, 100) }}%"></div>
                    </div>
                    @if($rifaAtiva->available_count <= 20)
                    <p class="text-yellow-200 text-sm font-bold mt-1 animate-pulse">🔥 Últimas {{ $rifaAtiva->available_count }} cotas!</p>
                    @endif
                </div>

                {{--Contador ou mensagem--}}
                @if($rifaAtiva->draw_date)
                <div x-data="countdown('{{ $rifaAtiva->draw_date->toISOString() }}')" class="flex gap-2 justify-center lg:justify-start mb-5">
                    <template x-if="!ended">
                        <div class="flex gap-2 items-center">
                            <span class="text-orange-200 text-sm">⏳</span>
                            <div class="bg-white/20 rounded-lg px-3 py-1.5 text-center"><p class="text-lg font-extrabold" x-text="days"></p><p class="text-xs text-orange-200">dias</p></div>
                            <div class="bg-white/20 rounded-lg px-3 py-1.5 text-center"><p class="text-lg font-extrabold" x-text="hours"></p><p class="text-xs text-orange-200">horas</p></div>
                            <div class="bg-white/20 rounded-lg px-3 py-1.5 text-center"><p class="text-lg font-extrabold" x-text="minutes"></p><p class="text-xs text-orange-200">min</p></div>
                            <div class="bg-white/20 rounded-lg px-3 py-1.5 text-center"><p class="text-lg font-extrabold" x-text="seconds"></p><p class="text-xs text-orange-200">seg</p></div>
                        </div>
                    </template>
                </div>
                @else
                <p class="text-yellow-200 text-sm mb-5">⚡ Sorteio quando todas as cotas forem vendidas!</p>
                @endif

                <a href="{{ route('destino-premiado.show', $rifaAtiva->slug) }}"
                   class="inline-block bg-white text-orange-600 hover:bg-orange-50 px-8 py-3.5 rounded-xl font-extrabold text-lg transition-colors shadow-lg">
                    🎟️ Garantir minha cota — R$ {{ number_format($rifaAtiva->ticket_price, 2, ',', '.') }}
                </a>
            </div>
        </div>
    </div>
</section>
@endif


{{--Mapa interativo--}}
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-16">
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-2xl p-8 flex flex-col sm:flex-row items-center justify-between gap-6 text-white">
        <div>
            <h2 class="text-2xl font-extrabold mb-2">🗺 Explore no mapa</h2>
            <p class="text-blue-100">Veja hotéis, pousadas e experiências no mapa interativo do Maranhão</p>
        </div>
        <a href="{{ route('mapa') }}" class="flex-shrink-0 bg-white text-blue-600 hover:bg-blue-50 px-8 py-3 rounded-xl font-bold transition-colors">
            Abrir mapa →
        </a>
    </div>
</section>


@endsection
