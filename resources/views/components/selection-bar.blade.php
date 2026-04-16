@props(['selectedDocumentIds', 'view', 'currentFolderId' => null])

@if(count($selectedDocumentIds) > 0)
    <div 
        x-data 
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-y-20 opacity-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-y-0 opacity-100"
        x-transition:leave-end="translate-y-20 opacity-0"
        class="fixed bottom-8 left-1/2 -translate-x-1/2 z-50 flex items-center gap-6 bg-white/80 dark:bg-zinc-900/80 backdrop-blur-2xl px-6 py-3 rounded-2xl border border-indigo-500/30 shadow-2xl shadow-indigo-500/20 ring-1 ring-black/5"
    >
        <div class="flex items-center gap-2">
            <div class="size-6 bg-indigo-600 rounded-lg flex items-center justify-center text-white text-[10px] font-black shadow-lg shadow-indigo-500/20">
                {{ count($selectedDocumentIds) }}
            </div>
            <span class="text-xs font-black text-zinc-900 dark:text-white uppercase tracking-widest whitespace-nowrap">Selecionados</span>
        </div>

        <div class="h-6 w-px bg-zinc-200 dark:bg-zinc-800"></div>
        
        <div class="flex items-center gap-1">
            @if($view === 'trash')
                <flux:button wire:click="restoreSelected" variant="ghost" size="sm" icon-leading="arrow-path" class="text-green-600 hover:bg-green-50">Restaurar</flux:button>
            @else
                @if($currentFolderId)
                    <flux:button wire:click="moveSelectedDocuments(null)" variant="ghost" size="sm" icon-leading="arrow-up-on-square" class="text-indigo-600 hover:bg-indigo-50">Mover para o Início</flux:button>
                @endif
                <flux:button wire:click="favoriteSelected" variant="ghost" size="sm" icon-leading="star" class="text-indigo-600 hover:bg-indigo-50">Favoritar</flux:button>
            @endif

            <flux:button wire:click="deleteSelected" wire:confirm="Excluir selecionados?" variant="ghost" size="sm" icon-leading="trash" class="text-red-600 hover:bg-red-50">
                {{ $view === 'trash' ? 'Excluir Permanente' : 'Excluir' }}
            </flux:button>
            
            <div class="h-6 w-px bg-zinc-200 dark:bg-zinc-800 mx-2"></div>

            <flux:button wire:click="clearSelection" variant="ghost" size="sm" class="font-bold">Cancelar</flux:button>
        </div>
    </div>
@endif
