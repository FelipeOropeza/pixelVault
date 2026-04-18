<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Folder;
use App\Models\Document;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    use WithFileUploads;

    #[\Livewire\Attributes\Url]
    public string $search = '';
    
    #[\Livewire\Attributes\Url]
    public string $filterType = 'all';
    
    #[\Livewire\Attributes\Reactive]
    public string $displayMode = 'grid';

    #[\Livewire\Attributes\Reactive]
    public ?int $currentFolderId = null;

    public $uploads = [];

    public function updatedSearch(): void
    {
        $this->dispatch('dashboard-refresh', search: $this->search);
    }

    public function setFilterType(string $type): void
    {
        $this->filterType = $type;
        $this->dispatch('dashboard-refresh', filterType: $type);
    }

    public function selectFolder(?int $id): void
    {
        $this->currentFolderId = $id;
        $this->dispatch('dashboard-refresh', folderId: $id);
    }

    public function updatedUploads(): void
    {
        if (empty($this->uploads)) return;

        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $totalSize = collect($this->uploads)->sum(fn($file) => $file->getSize());

        if (!$user->hasAvailableStorage($totalSize)) {
            $this->uploads = [];
            $this->dispatch('notify', 'Sem espaço disponível para todos os arquivos. Faça um upgrade!');
            return;
        }

        $documentsData = [];
        foreach ($this->uploads as $file) {
            $path = $file->store('documents/' . $user->id, 'public');

            $documentsData[] = [
                'user_id'    => $user->id,
                'folder_id'  => $this->currentFolderId,
                'name'       => $file->getClientOriginalName(),
                'path'       => $path,
                'size_bytes' => $file->getSize(),
                'mime_type'  => $file->getMimeType(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($documentsData)) {
            Document::insert($documentsData);
            $user->addStorageUsage($totalSize);
        }

        $this->uploads = [];
        $this->dispatch('notify', 'Arquivos enviados com sucesso!');
        $this->dispatch('dashboard-refresh');
    }

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [];
        $current = $this->currentFolderId ? Folder::with('parent.parent.parent.parent.parent')->find($this->currentFolderId) : null;
        while ($current) {
            array_unshift($breadcrumbs, $current);
            $current = $current->parent;
        }
        return $breadcrumbs;
    }
};
?>

<div 
    class="flex flex-col gap-8 mb-12"
    x-on:trigger-upload.window="$wire.uploadMultiple('uploads', $event.detail.files)"
>
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="space-y-1">
            <div class="flex items-center gap-3">
                @if($currentFolderId)
                    <flux:button wire:click="selectFolder(null)" variant="ghost" size="sm" icon="arrow-left" class="text-zinc-400 hover:text-indigo-600 p-0" />
                @endif
                <h1 class="text-4xl font-black tracking-tight text-zinc-900 dark:text-white leading-none">Meu Cofre</h1>
            </div>
            
            <div class="flex items-center gap-2 text-zinc-500 dark:text-zinc-400 font-medium tracking-tight">
                <button wire:click="selectFolder(null)" class="hover:text-indigo-600 transition-colors">Início</button>
                @foreach($this->getBreadcrumbs() as $breadcrumb)
                    <flux:icon icon="chevron-right" class="size-3" />
                    <button wire:click="selectFolder({{ $breadcrumb->id }})" class="hover:text-indigo-600 transition-colors">
                        {{ $breadcrumb->name }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="flex items-center gap-3">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Pesquisar em tudo..."
                icon="magnifying-glass"
                class="w-64"
            />
            
            <label class="cursor-pointer">
                <input type="file" wire:model="uploads" class="hidden" multiple>
                <flux:button as="div" variant="primary" icon-leading="plus" class="bg-indigo-600 hover:bg-indigo-700 h-11 px-6 shadow-lg shadow-indigo-500/20">
                    <span>Novo Upload</span>
                </flux:button>
            </label>
        </div>
    </div>

    @error('uploads')
        <div class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-sm font-bold rounded-2xl border border-red-100 dark:border-red-800 flex items-center gap-3">
            <flux:icon icon="exclamation-circle" class="size-5 shrink-0" />
            {{ $message }}
        </div>
    @enderror
    @error('uploads.*')
        <div class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-sm font-bold rounded-2xl border border-red-100 dark:border-red-800 flex items-center gap-3">
            <flux:icon icon="exclamation-circle" class="size-5 shrink-0" />
            {{ $message }}
        </div>
    @enderror

    {{-- Filtros e Modo de Exibição --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-2">
            @foreach(['all' => 'Todos', 'image' => 'Imagens', 'video' => 'Vídeos', 'pdf' => 'PDFs', 'audio' => 'Áudios', 'archive' => 'Arquivos'] as $type => $label)
                <flux:button 
                    wire:click="setFilterType('{{ $type }}')" 
                    size="sm" 
                    variant="{{ $filterType === $type ? 'primary' : 'ghost' }}" 
                    class="{{ $filterType === $type ? 'bg-indigo-600' : '' }}"
                >
                    {{ $label }}
                </flux:button>
            @endforeach
        </div>

        <div class="flex items-center bg-zinc-100 dark:bg-zinc-800 rounded-lg p-1">
            <button wire:click="$parent.set('displayMode', 'grid')" class="p-1 px-2 rounded-md {{ $displayMode === 'grid' ? 'bg-white dark:bg-zinc-700 shadow-sm text-indigo-600' : 'text-zinc-500' }}">
                <flux:icon icon="squares-2x2" class="size-4" />
            </button>
            <button wire:click="$parent.set('displayMode', 'list')" class="p-1 px-2 rounded-md {{ $displayMode === 'list' ? 'bg-white dark:bg-zinc-700 shadow-sm text-indigo-600' : 'text-zinc-500' }}">
                <flux:icon icon="list-bullet" class="size-4" />
            </button>
        </div>
    </div>

    {{-- Loading Overlay de Upload - Integrado na Ilha da Toolbar --}}
    <div
        wire:loading.flex
        wire:target="uploads"
        class="fixed inset-0 z-[9999] items-center justify-center bg-zinc-950/80 backdrop-blur-md p-6"
    >
        <div class="bg-white dark:bg-zinc-900 rounded-[3rem] p-12 shadow-2xl flex flex-col items-center justify-center gap-8 border border-zinc-200 dark:border-zinc-800 max-w-sm w-full"
             x-data="{ progress: 0 }"
             x-on:livewire-upload-progress="progress = $event.detail.progress"
        >
            <div class="relative size-32 flex items-center justify-center">
                {{-- Círculo de Progresso --}}
                <svg class="size-full -rotate-90" viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="16" fill="none" class="stroke-zinc-100 dark:stroke-zinc-800" stroke-width="3"></circle>
                    <circle cx="18" cy="18" r="16" fill="none" class="stroke-indigo-600 transition-all duration-300" stroke-width="3" 
                        stroke-dasharray="100" :stroke-dashoffset="100 - progress" stroke-linecap="round"></circle>
                </svg>
                <span class="absolute text-2xl font-black text-zinc-900 dark:text-white" x-text="progress + '%'"></span>
            </div>

            <div class="text-center space-y-2">
                <h3 class="text-2xl font-black text-zinc-900 dark:text-white tracking-tight">Enviando Arquivos</h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 font-medium max-w-[200px] mx-auto">Preparando seus arquivos para o cofre...</p>
            </div>

            <div class="w-full bg-zinc-100 dark:bg-zinc-800 h-2.5 rounded-full overflow-hidden shadow-inner">
                <div class="bg-indigo-600 h-full transition-all duration-300 shadow-[0_0_10px_rgba(79,70,229,0.5)]" :style="{ width: progress + '%' }"></div>
            </div>
        </div>
    </div>
</div>
