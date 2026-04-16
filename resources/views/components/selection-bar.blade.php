@props(['selectedDocumentIds', 'view', 'currentFolderId' => null])

@if(count($selectedDocumentIds) > 0)
    <div 
        x-data 
        x-transition
        class="flex items-center gap-3 bg-indigo-50 dark:bg-indigo-900/20 px-4 py-2 rounded-2xl border border-indigo-100 dark:border-indigo-800"
    >
        <span class="text-xs font-black text-indigo-600 dark:text-indigo-400">{{ count($selectedDocumentIds) }} selecionados</span>
        <div class="h-4 w-px bg-indigo-200 dark:bg-indigo-800"></div>
        
        @if($view === 'trash')
            <flux:button wire:click="restoreSelected" variant="ghost" size="xs" icon="arrow-path" class="text-green-600">Restaurar</flux:button>
        @else
            @if($currentFolderId)
                <flux:button wire:click="moveSelectedDocuments(null)" variant="ghost" size="xs" icon="arrow-up-on-square" class="text-indigo-600">Mover para o Início</flux:button>
            @endif
            <flux:button wire:click="favoriteSelected" variant="ghost" size="xs" icon="star" class="text-indigo-600">Favoritar</flux:button>
        @endif

        <flux:button wire:click="deleteSelected" wire:confirm="Excluir selecionados?" variant="ghost" size="xs" icon="trash" class="text-red-600">
            {{ $view === 'trash' ? 'Excluir Permanentemente' : 'Excluir' }}
        </flux:button>
        <flux:button wire:click="clearSelection" variant="ghost" size="xs">Cancelar</flux:button>
    </div>
@endif
