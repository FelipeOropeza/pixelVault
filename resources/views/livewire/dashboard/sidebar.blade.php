<?php

use Livewire\Component;
use App\Models\Folder;
use Illuminate\Support\Facades\Auth;
use App\Traits\HasFileHelpers;

new class extends Component {
    use HasFileHelpers;
    // Definimos as propriedades como reativas/sincronizadas com o pai ou via URL
    #[\Livewire\Attributes\Url]
    public string $view = 'all';
    
    #[\Livewire\Attributes\Url]
    public ?int $currentFolderId = null;
    
    public bool $isCreatingFolder = false;
    public string $newFolderName = '';
    public string $search = '';

    #[\Livewire\Attributes\On('dashboard-refresh')]
    public function refreshSidebar(): void
    {
        // Apenas para forçar o re-render do sidebar (stats de armazenamento)
    }

    public function setView(string $view): void
    {
        $this->view = $view;
        $this->currentFolderId = null;
        $this->dispatch('dashboard-refresh', view: $view, folderId: null);
    }

    public function selectFolder(?int $id): void
    {
        $this->currentFolderId = $id;
        $this->view = 'all';
        $this->dispatch('dashboard-refresh', view: 'all', folderId: $id);
    }

    public function createFolder(): void
    {
        if (empty($this->newFolderName)) return;

        Auth::user()->folders()->create([
            'name' => $this->newFolderName,
            'parent_id' => $this->currentFolderId,
        ]);

        $this->newFolderName = '';
        $this->isCreatingFolder = false;
        $this->dispatch('dashboard-refresh');
        $this->dispatch('notify', 'Pasta criada com sucesso!');
    }

    public function getPercentage(): float
    {
        $user = Auth::user();
        if (!$user->plan || $user->plan->storage_limit_bytes === 0) return 0;
        return min(($user->storage_used_bytes / $user->plan->storage_limit_bytes) * 100, 100);
    }


};
?>

<aside class="lg:col-span-1 space-y-8">
    {{-- Espaço em Nuvem - Uma Ilha interna para performance --}}

        <div class="px-2" x-data="{ percentage: {{ $this->getPercentage() }} }">
            <h3 class="text-xs font-black uppercase tracking-widest text-zinc-400 dark:text-zinc-500 mb-4 px-4">Espaço em Nuvem</h3>
            <div class="p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold text-zinc-600 dark:text-zinc-400">
                        {{ auth()->user()->plan?->name ?? 'Sem Plano' }}
                    </span>
                    <span class="text-xs font-black text-indigo-600">
                        {{ round($this->getPercentage(), 1) }}%
                    </span>
                </div>

                <div class="w-full bg-zinc-200 dark:bg-zinc-700 h-1.5 rounded-full overflow-hidden mb-3 shadow-inner">
                    <div
                        class="h-full rounded-full transition-all duration-700 shadow-[0_0_8px_rgba(79,70,229,0.3)]"
                        :class="{ 'bg-red-500': percentage >= 90, 'bg-indigo-600': percentage < 90 }"
                        :style="{ width: percentage + '%' }"
                    ></div>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-medium text-zinc-500">
                        {{ $this->formatBytes(auth()->user()->storage_used_bytes) }} / {{ $this->formatBytes(auth()->user()->plan?->storage_limit_bytes ?? 0) }}
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
            <h3 class="text-xs font-black uppercase tracking-widest text-zinc-400 dark:text-zinc-500 mb-4 px-4 flex items-center justify-between">
                <span>Minhas Pastas</span>
                <button wire:click="$toggle('isCreatingFolder')" class="text-indigo-600 hover:scale-110 transition-transform">
                    <flux:icon icon="plus-circle" class="size-4" />
                </button>
            </h3>

            @if($isCreatingFolder)
                <div class="px-4 mb-4 animate-in slide-in-from-top-2 duration-200">
                    <flux:input 
                        wire:model="newFolderName" 
                        wire:keydown.enter="createFolder"
                        placeholder="Nome da pasta..." 
                        size="sm"
                        autofocus
                    />
                    <div class="flex gap-2 mt-2">
                        <flux:button wire:click="createFolder" size="xs" variant="primary" class="bg-indigo-600">Criar</flux:button>
                        <flux:button wire:click="$set('isCreatingFolder', false)" size="xs" variant="ghost">Cancelar</flux:button>
                    </div>
                </div>
            @endif

            <div class="space-y-1">
                @php
                    $rootFolders = auth()->user()->folders()->where('parent_id', null)->get();
                @endphp
                @foreach($rootFolders as $folder)
                    <flux:button 
                        wire:click="selectFolder({{ $folder->id }})" 
                        variant="ghost" 
                        class="w-full justify-start gap-3 py-2 text-sm font-medium {{ $currentFolderId === $folder->id ? 'bg-indigo-50 dark:bg-indigo-900/10 text-indigo-600' : 'text-zinc-500' }}"
                        icon-leading="folder"
                    >
                        {{ $folder->name }}
                    </flux:button>
                @endforeach
            </div>
        </div>
    @endif
</aside>
