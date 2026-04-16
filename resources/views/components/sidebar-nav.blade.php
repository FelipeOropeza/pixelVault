@props(['currentFolderId', 'view', 'isCreatingFolder', 'search'])

<aside class="lg:col-span-1 space-y-8">
    {{-- Upload Section --}}
    @if($view === 'all')
        <div class="bg-indigo-600 rounded-3xl p-6 text-white shadow-xl shadow-indigo-500/20">
            <h3 class="text-lg font-black mb-2 flex items-center gap-2">
                <flux:icon icon="cloud-arrow-up" variant="solid" class="size-5" />
                Upload
            </h3>
            <p class="text-xs text-indigo-100 mb-6 font-medium leading-relaxed">Arraste arquivos aqui ou use o botão abaixo para enviar.</p>
            
            <label class="block group cursor-pointer">
                <input type="file" wire:model="file" class="hidden">
                <div class="w-full py-4 bg-white/10 hover:bg-white/20 border-2 border-dashed border-white/30 rounded-2xl flex flex-col items-center justify-center transition-all group-hover:scale-[1.02] active:scale-95">
                    <flux:icon icon="plus" class="size-6 mb-1" />
                    <span class="text-xs font-black uppercase tracking-wider">Selecionar</span>
                </div>
            </label>

            <div wire:loading wire:target="file" class="mt-4 w-full">
                <div class="flex items-center gap-2 mb-2">
                    <flux:spacer />
                    <span class="text-[10px] font-black uppercase tracking-widest animate-pulse">Enviando...</span>
                </div>
                <div class="h-1.5 w-full bg-white/20 rounded-full overflow-hidden">
                    <div class="h-full bg-white rounded-full animate-progress" style="width: 100%"></div>
                </div>
            </div>
        </div>
    @endif

    {{-- Espaço em Nuvem --}}
    <div class="px-2">
        <h3 class="text-xs font-black uppercase tracking-widest text-zinc-400 dark:text-zinc-500 mb-4 px-4">Espaço em Nuvem</h3>
        <div class="p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-2xl border border-zinc-200 dark:border-zinc-800">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-zinc-600 dark:text-zinc-400">
                    {{ auth()->user()->plan?->name ?? 'Sem Plano' }}
                </span>
                <span class="text-xs font-black text-indigo-600">
                    {{ round($this->getPercentage(), 1) }}%
                </span>
            </div>

            <div class="w-full bg-zinc-200 dark:bg-zinc-700 h-1.5 rounded-full overflow-hidden mb-3">
                <div
                    class="h-full rounded-full transition-all duration-500 {{ $this->getPercentage() >= 90 ? 'bg-red-500' : 'bg-indigo-600' }}"
                    style="width: {{ $this->getPercentage() }}%"
                ></div>
            </div>

            <div class="flex items-center justify-between">
                <span class="text-[10px] font-medium text-zinc-500">
                    {{ $this->formatBytes(auth()->user()->storage_used_bytes) }}
                    de
                    {{ $this->formatBytes(auth()->user()->plan?->storage_limit_bytes ?? 0) }}
                </span>
                <flux:link href="/plans" icon="arrow-up-circle" class="text-[10px] font-black text-indigo-600">Upgrade</flux:link>
            </div>
        </div>
    </div>

    {{-- Navigation --}}
    <div class="space-y-1">
        <flux:button 
            wire:click="setView('all')" 
            variant="ghost" 
            class="w-full justify-start gap-3 py-3 font-bold {{ $view === 'all' && !$search ? 'bg-indigo-50 dark:bg-indigo-900/10 text-indigo-600' : 'text-zinc-500' }}"
            icon-leading="squares-2x2"
        >
            Meu Cofre
        </flux:button>
        <flux:button 
            wire:click="setView('favorites')" 
            variant="ghost" 
            class="w-full justify-start gap-3 py-3 font-bold {{ $view === 'favorites' ? 'bg-indigo-50 dark:bg-indigo-900/10 text-indigo-600' : 'text-zinc-500' }}"
            icon-leading="star"
        >
            Favoritos
        </flux:button>
        <flux:button 
            wire:click="setView('trash')" 
            variant="ghost" 
            class="w-full justify-start gap-3 py-3 font-bold {{ $view === 'trash' ? 'bg-indigo-50 dark:bg-indigo-900/10 text-indigo-600' : 'text-zinc-500' }}"
            icon-leading="trash"
        >
            Lixeira
        </flux:button>
    </div>

    {{-- Pastas Sidebar --}}
    @if($view === 'all')
        <div class="px-2 pt-4 border-t border-zinc-100 dark:border-zinc-800">
            <div class="space-y-1">
                <flux:button
                    wire:click="selectFolder(null)"
                    variant="ghost"
                    class="w-full justify-start text-zinc-600 dark:text-zinc-400 font-bold {{ $currentFolderId === null && $view === 'all' ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600' : '' }}"
                    icon-leading="home"
                >
                    Todos os Arquivos
                </flux:button>
            </div>

            <h3 class="text-xs font-black uppercase tracking-widest text-zinc-400 dark:text-zinc-500 mb-4 mt-6 px-4 flex items-center justify-between">
                <span>Minhas Pastas</span>
                <flux:button wire:click="$set('isCreatingFolder', true)" variant="ghost" size="xs" icon="plus" class="text-zinc-400" />
            </h3>
            
            <div class="space-y-1">
                @foreach(auth()->user()->folders()->where('parent_id', null)->get() as $sidebarFolder)
                    <flux:button
                        x-data="{ over: false }"
                        @dragover.prevent="over = true"
                        @dragleave="over = false"
                        @drop="over = false; $wire.selectedDocumentIds.length > 0 ? $wire.moveSelectedDocuments({{ $sidebarFolder->id }}) : $wire.moveDocument($event.dataTransfer.getData('docId'), {{ $sidebarFolder->id }})"
                        wire:click="selectFolder({{ $sidebarFolder->id }})"
                        variant="ghost"
                        class="w-full justify-start text-zinc-500 {{ $currentFolderId === $sidebarFolder->id ? 'bg-zinc-100 dark:bg-zinc-800 text-indigo-600' : '' }}"
                        x-bind:class="over ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600' : ''"
                        icon-leading="folder"
                    >
                        {{ $sidebarFolder->name }}
                    </flux:button>
                @endforeach

                @if($isCreatingFolder)
                    <div class="px-4 py-2 bg-indigo-50/50 dark:bg-indigo-900/5 rounded-2xl border border-indigo-100 dark:border-indigo-800/50 mt-2 animate-in fade-in slide-in-from-top-2">
                        <input 
                            wire:model="newFolderName" 
                            wire:keydown.enter="createFolder"
                            type="text" 
                            placeholder="Nome da pasta..."
                            class="w-full bg-transparent border-none focus:ring-0 text-sm font-bold text-zinc-700 dark:text-zinc-300 placeholder-zinc-400 p-0"
                            autofocus
                        >
                        <div class="flex items-center gap-2 mt-2 pt-2 border-t border-indigo-100/50">
                            <flux:button wire:click="createFolder" variant="primary" size="xs" class="flex-1 bg-indigo-600">Criar</flux:button>
                            <flux:button wire:click="$set('isCreatingFolder', false)" variant="ghost" size="xs">X</flux:button>
                        </div>
                    </div>
                @else
                    <flux:button wire:click="$set('isCreatingFolder', true)" variant="ghost" class="w-full justify-start text-zinc-400 hover:text-indigo-600" icon-leading="plus">Nova Pasta</flux:button>
                @endif
            </div>
        </div>
    @endif
</aside>
