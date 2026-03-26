<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{--SEO: Title--}}
    <title>@yield('title', 'Reservar Destinos') — Turismo no Maranhão</title>

    {{--SEO: Meta description e keywords--}}
    <meta name="description" content="@yield('seo_description', 'Reserve hotéis, pousadas e experiências no Maranhão. Lençóis Maranhenses, São Luís, Barreirinhas e muito mais. Seu próximo destino começa aqui.')">
    <meta name="keywords" content="@yield('seo_keywords', 'hotéis maranhão, lençóis maranhenses, são luís, barreirinhas, turismo maranhão, reservar hotel, pousada')">
    <meta name="robots" content="index, follow">
    <link rel="sitemap" type="application/xml" href="{{ url('/sitemap.xml') }}">
    <link rel="canonical" href="{{ url()->current() }}">

    {{--Open Graph (WhatsApp, Facebook)--}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Reservar Destinos">
    <meta property="og:title" content="@yield('title', 'Reservar Destinos') — Turismo no Maranhão">
    <meta property="og:description" content="@yield('seo_description', 'Reserve hotéis, pousadas e experiências no Maranhão.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="@yield('seo_image', asset('images/logo.png'))">
    <meta property="og:locale" content="pt_BR">

    {{--Twitter Card--}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', 'Reservar Destinos') — Turismo no Maranhão">
    <meta name="twitter:description" content="@yield('seo_description', 'Reserve hotéis, pousadas e experiências no Maranhão.')">
    <meta name="twitter:image" content="@yield('seo_image', asset('images/logo.png'))">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="bg-gray-50 text-gray-900 antialiased">

<header class="bg-white shadow-sm sticky top-0 z-50" x-data="{ open: false }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <a href="{{ route('home') }}" class="flex items-center">
                <img src="{{ asset('images/logo.png') }}" alt="Reservar Destinos" class="h-10 w-auto">
            </a>
            <nav class="hidden md:flex items-center gap-8">
                <a href="{{ route('home') }}" class="text-gray-600 hover:text-orange-500 font-medium transition-colors">Início</a>
                <a href="{{ route('destinos') }}" class="text-gray-600 hover:text-orange-500 font-medium transition-colors">Destinos</a>
                <a href="{{ route('hoteis') }}" class="text-gray-600 hover:text-orange-500 font-medium transition-colors">Hotéis</a>
                <a href="{{ route('experiencias') }}" class="text-gray-600 hover:text-orange-500 font-medium transition-colors">Experiências</a>
                <a href="{{ route('planejador') }}" class="flex items-center gap-1 text-blue-600 hover:text-blue-700 font-semibold transition-colors">
                    ✈️ Monte sua viagem
                </a>
                <a href="{{ route('destino-premiado') }}" class="flex items-center gap-1 text-orange-500 hover:text-orange-600 font-semibold transition-colors">
                    🏆 Destino Premiado
                </a>
            </nav>
            <div class="hidden md:flex items-center gap-3">
                @auth
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="flex items-center gap-2 bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        <span class="w-6 h-6 bg-orange-500 text-white rounded-full flex items-center justify-center text-xs font-bold">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </span>
                        {{ Str::words(Auth::user()->name, 1, '') }}
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-cloak @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border py-2 z-50">
                        <a href="{{ route('painel') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-orange-50 hover:text-orange-500">📊 Meu Painel</a>
                        <a href="{{ route('favoritos') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-orange-50 hover:text-orange-500">❤️ Favoritos</a>
                        <a href="{{ route('painel.reservas') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-orange-50 hover:text-orange-500">🏨 Minhas Reservas</a>
                        <a href="{{ route('painel.participacoes') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-orange-50 hover:text-orange-500">🏆 Destino Premiado</a>
                        <a href="{{ route('painel.perfil') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-orange-50 hover:text-orange-500">👤 Meu Perfil</a>
                        <div class="border-t my-1"></div>
                        @if(Auth::user()->isParceiro())
                        @if(Auth::user()->partner_type === 'experience')
                        <a href="{{ route('parceiro.exp.dashboard') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-green-50 hover:text-green-600">🎒 Painel do Parceiro</a>
                        @else
                        <a href="{{ route('parceiro.dashboard') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600">🏨 Painel do Parceiro</a>
                        @endif
                        @endif
                        <div class="border-t my-1"></div>
                        @if(Auth::user()->isAdmin())
                        <a href="/admin" class="block px-4 py-2 text-sm text-orange-500 hover:bg-orange-50 font-medium">⚙️ Painel Admin</a>
                        <div class="border-t my-1"></div>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-500 hover:bg-red-50">🚪 Sair</button>
                        </form>
                    </div>
                </div>
                @else
                <a href="{{ route('login') }}" class="text-gray-600 hover:text-orange-500 font-medium text-sm transition-colors">Entrar</a>
                <a href="{{ route('parceiro.cadastro') }}" class="text-blue-600 hover:text-blue-700 font-medium text-sm transition-colors border border-blue-200 px-3 py-2 rounded-lg">🏨 Seja parceiro</a>
                <a href="{{ route('register') }}" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg font-medium text-sm transition-colors">Criar conta</a>
                @endauth
            </div>
            <button @click="open = !open" class="md:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100">
                <svg x-show="!open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                <svg x-show="open" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div x-show="open" x-cloak class="md:hidden border-t pb-4 pt-2 space-y-1">
            <a href="{{ route('home') }}" class="block px-3 py-2 text-gray-700 hover:bg-orange-50 hover:text-orange-500 rounded-lg font-medium">Início</a>
            <a href="{{ route('destinos') }}" class="block px-3 py-2 text-gray-700 hover:bg-orange-50 hover:text-orange-500 rounded-lg font-medium">Destinos</a>
            <a href="{{ route('hoteis') }}" class="block px-3 py-2 text-gray-700 hover:bg-orange-50 hover:text-orange-500 rounded-lg font-medium">Hotéis</a>
            <a href="{{ route('experiencias') }}" class="block px-3 py-2 text-gray-700 hover:bg-orange-50 hover:text-orange-500 rounded-lg font-medium">Experiências</a>
            <a href="{{ route('planejador') }}" class="block px-3 py-2 text-blue-600 font-semibold">✈️ Monte sua viagem</a>
            <a href="{{ route('destino-premiado') }}" class="block px-3 py-2 text-orange-500 font-semibold">🏆 Destino Premiado</a>
            @auth
            <a href="{{ route('painel') }}" class="block px-3 py-2 text-gray-700 hover:bg-orange-50 hover:text-orange-500 rounded-lg font-medium">📊 Meu Painel</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full text-left px-3 py-2 text-red-500 font-medium">🚪 Sair</button>
            </form>
            @else
            <a href="{{ route('login') }}" class="block px-3 py-2 text-gray-700 hover:bg-orange-50 hover:text-orange-500 rounded-lg font-medium">Entrar</a>
            <a href="{{ route('register') }}" class="block px-3 py-2 text-orange-500 font-semibold">Criar conta grátis →</a>
            <a href="{{ route('parceiro.cadastro') }}" class="block px-3 py-2 text-blue-600 font-semibold">🏨 Cadastre seu hotel →</a>
            @endauth
        </div>
    </div>
</header>

<main>@yield('content')</main>

<footer class="bg-gray-900 text-white mt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div>
                <div class="mb-4">
                    <img src="{{ asset('images/logo.png') }}" alt="Reservar Destinos" class="h-10 w-auto brightness-0 invert">
                </div>
                <p class="text-gray-400 text-sm leading-relaxed">Seu próximo destino começa aqui. Explore o melhor do Maranhão com segurança e praticidade.</p>
            </div>
            <div>
                <h4 class="font-semibold mb-4 text-gray-200">Explore</h4>
                <ul class="space-y-2 text-gray-400 text-sm">
                    <li><a href="{{ route('destinos') }}" class="hover:text-orange-400 transition-colors">Destinos</a></li>
                    <li><a href="{{ route('mapa') }}" class="hover:text-orange-400 transition-colors">🗺 Mapa interativo</a></li>
                    <li><a href="{{ route('hoteis') }}" class="hover:text-orange-400 transition-colors">Hotéis e Pousadas</a></li>
                    <li><a href="{{ route('experiencias') }}" class="hover:text-orange-400 transition-colors">Experiências</a></li>
                    <li><a href="{{ route('destino-premiado') }}" class="hover:text-orange-400 transition-colors">🏆 Destino Premiado</a></li>
                    <li><a href="{{ route('parceiro.cadastro') }}" class="hover:text-orange-400 transition-colors">🏨 Cadastre seu hotel</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-semibold mb-4 text-gray-200">Destaques</h4>
                <ul class="space-y-2 text-gray-400 text-sm">
                    <li>🏖️ Lençóis Maranhenses</li>
                    <li>🏛️ São Luís — Patrimônio UNESCO</li>
                    <li>🌿 Chapada das Mesas</li>
                    <li>⛵ Alcântara Histórica</li>
                </ul>
            </div>
        </div>
        <div class="border-t border-gray-800 mt-10 pt-6 text-center text-gray-500 text-sm">
            © {{ date('Y') }} ReservarDestinos todos os direitos reservados — Cleyanne Teixeira
                <div class="flex justify-center gap-4 mt-2">
                    <a href="{{ route('legal.termos') }}" class="hover:text-gray-300 transition-colors">Termos de Uso</a>
                    <span>·</span>
                    <a href="{{ route('legal.privacidade') }}" class="hover:text-gray-300 transition-colors">Política de Privacidade</a>
                </div>
        </div>
    </div>
</footer>

<script>
function toggleFavorito(btn) {
    var type = btn.dataset.favType;
    var id   = btn.dataset.favId;
    fetch('/favoritos/toggle', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
        },
        body: JSON.stringify({ type: type, id: id })
    })
    .then(r => r.json())
    .then(data => {
        btn.dataset.favActive = data.favoritado ? '1' : '0';
        var svg = btn.querySelector('svg');
        if (data.favoritado) {
            btn.classList.add('bg-red-500', 'text-white');
            btn.classList.remove('bg-white/80', 'text-gray-400', 'hover:text-red-500', 'hover:bg-white');
            svg.setAttribute('fill', 'currentColor');
            btn.title = 'Remover dos favoritos';
        } else {
            btn.classList.remove('bg-red-500', 'text-white');
            btn.classList.add('bg-white/80', 'text-gray-400', 'hover:text-red-500', 'hover:bg-white');
            svg.setAttribute('fill', 'none');
            btn.title = 'Salvar nos favoritos';
            if (window.location.pathname.includes('favoritos')) {
                var card = btn.closest('.relative');
                if (card) card.remove();
            }
        }
    });
}
</script>
<script>
function countdown(targetDate) {
    return {
        days: '00', hours: '00', minutes: '00', seconds: '00',
        ended: false,
        init() { this.update(); setInterval(() => this.update(), 1000); },
        update() {
            const diff = new Date(targetDate) - new Date();
            if (diff <= 0) { this.ended = true; return; }
            this.days    = String(Math.floor(diff / 86400000)).padStart(2, '0');
            this.hours   = String(Math.floor((diff % 86400000) / 3600000)).padStart(2, '0');
            this.minutes = String(Math.floor((diff % 3600000) / 60000)).padStart(2, '0');
            this.seconds = String(Math.floor((diff % 60000) / 1000)).padStart(2, '0');
        }
    }
}
</script>
</body>
</html>
