<x-filament-panels::page>

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-6 max-w-xl">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Rifa em destaque na home</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
            Escolha qual rifa ativa aparece em destaque na página inicial e no banner fixo do site.
            Se deixar em branco, a rifa mais próxima do sorteio será exibida automaticamente.
        </p>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Rifa em destaque
                </label>
                <select wire:model="rifaDestaqueId"
                    class="w-full border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-3 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-amber-400">
                    <option value="">— Automático (mais próxima do sorteio) —</option>
                    @foreach($this->getRifasAtivas() as $id => $titulo)
                        <option value="{{ $id }}">{{ $titulo }}</option>
                    @endforeach
                </select>
            </div>

            <button wire:click="salvar"
                class="bg-amber-500 hover:bg-amber-600 text-white px-6 py-2.5 rounded-xl font-bold text-sm transition-colors">
                Salvar configuração
            </button>
        </div>

        @if($rifaDestaqueId)
        <div class="mt-6 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 rounded-xl">
            <p class="text-sm text-amber-700 dark:text-amber-400 font-medium">
                ✅ Rifa em destaque selecionada manualmente. Para voltar ao automático, selecione "Automático" e salve.
            </p>
        </div>
        @else
        <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-xl">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                ℹ️ Modo automático ativo: exibe a rifa com sorteio mais próximo.
            </p>
        </div>
        @endif
    </div>

</x-filament-panels::page>
