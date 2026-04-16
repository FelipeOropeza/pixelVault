<?php // resources/views/pages/⚡dashboard.blade.php

use Livewire\WithFileUploads;
use Livewire\Component;
use App\Models\Document;
use App\Models\Folder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    use WithFileUploads;

    public $file;
    public string $successMessage = '';
    public string $search = '';
    public ?int $currentFolderId = null;
    public string $view = 'all'; // all, favorites, trash
    public string $filterType = 'all'; // all, image, video, pdf, audio, archive
    public string $newFolderName = '';
    public bool $isCreatingFolder = false;
    public array $selectedDocumentIds = [];
    public string $displayMode = 'grid'; // grid, list

    public function toggleSelection(int $id, string $type = 'file'): void
    {
        $key = "$type:$id";
        if (in_array($key, $this->selectedDocumentIds)) {
            $this->selectedDocumentIds = array_diff($this->selectedDocumentIds, [$key]);
        } else {
            $this->selectedDocumentIds[] = $key;
        }
    }

    public function clearSelection(): void
    {
        $this->selectedDocumentIds = [];
    }

    public function deleteSelected(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $fileIds = collect($this->selectedDocumentIds)->filter(fn($k) => str_starts_with($k, 'file:'))->map(fn($k) => (int) explode(':', $k)[1]);
        $folderIds = collect($this->selectedDocumentIds)->filter(fn($k) => str_starts_with($k, 'folder:'))->map(fn($k) => (int) explode(':', $k)[1]);

        $docs = $user->documents()->withTrashed()->whereIn('id', $fileIds)->get();
        $folders = $user->folders()->whereIn('id', $folderIds)->get();

        foreach ($docs as $doc) {
            if ($this->view === 'trash') {
                Storage::disk('public')->delete($doc->path);
                $user->reduceStorageUsage($doc->size_bytes);
                $doc->forceDelete();
            } else {
                $doc->delete();
            }
        }

        $this->clearSelection();
        $this->successMessage = count($docs) . ' arquivos processados.';
    }

    public function favoriteSelected(): void
    {
        $fileIds = collect($this->selectedDocumentIds)->filter(fn($k) => str_starts_with($k, 'file:'))->map(fn($k) => (int) explode(':', $k)[1]);
        Auth::user()->documents()->whereIn('id', $fileIds)->update(['is_favorite' => true]);
        $this->clearSelection();
        $this->successMessage = 'Arquivos favoritados com sucesso.';
    }

    public function restoreSelected(): void
    {
        $fileIds = collect($this->selectedDocumentIds)->filter(fn($k) => str_starts_with($k, 'file:'))->map(fn($k) => (int) explode(':', $k)[1]);
        Auth::user()->documents()->onlyTrashed()->whereIn('id', $fileIds)->restore();
        $this->clearSelection();
        $this->successMessage = 'Arquivos restaurados com sucesso.';
    }

    public function moveSelectedDocuments(?int $targetFolderId): void
    {
        if (empty($this->selectedDocumentIds)) return;

        $fileIds = collect($this->selectedDocumentIds)->filter(fn($k) => str_starts_with($k, 'file:'))->map(fn($k) => (int) explode(':', $k)[1]);
        Auth::user()->documents()->withTrashed()->whereIn('id', $fileIds)->update(['folder_id' => $targetFolderId]);
        
        $count = count($this->selectedDocumentIds);
        $this->clearSelection();
        $this->successMessage = "$count arquivos movidos com sucesso!";
    }

    public function moveDocument(int $docId, ?int $targetFolderId): void
    {
        $doc = Auth::user()->documents()->withTrashed()->findOrFail($docId);
        $doc->update(['folder_id' => $targetFolderId]);

        $this->successMessage = 'Arquivo movido com sucesso!';
    }

    public function deleteFolder(int $id, bool $force = false): void
    {
        $folder = Auth::user()->folders()->withCount('documents')->findOrFail($id);

        if ($folder->documents_count > 0 && !$force) {
            $this->js("if(confirm('Esta pasta contém {$folder->documents_count} arquivos. Deseja excluir a pasta e mover todos os arquivos para a lixeira?')) { \$wire.deleteFolder($id, true) }");
            return;
        }

        // Move arquivos para a lixeira se houver
        if ($folder->documents_count > 0) {
            $folder->documents()->update(['deleted_at' => now()]);
        }

        $folder->delete();
        $this->successMessage = 'Pasta excluída com sucesso!';
        
        if ($this->currentFolderId === $id) {
            $this->selectFolder(null);
        }
    }

    public function updatedFile(): void
    {
        $this->validate([
            'file' => 'required|file|max:20480', // Max 20MB
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $size = $this->file->getSize();

        if (!$user->hasAvailableStorage($size)) {
            $this->addError('file', 'Sem espaço disponível. Faça um upgrade de plano!');
            $this->file = null;
            return;
        }

        $path = $this->file->store('documents/' . $user->id, 'public');

        Document::create([
            'user_id'   => $user->id,
            'folder_id' => $this->currentFolderId,
            'name'      => $this->file->getClientOriginalName(),
            'path'      => $path,
            'size_bytes'=> $size,
            'mime_type' => $this->file->getMimeType(),
        ]);

        $user->addStorageUsage($size);
        $this->file = null;
        $this->successMessage = 'Arquivo enviado com sucesso!';
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
        $this->successMessage = 'Pasta criada com sucesso!';
    }

    public function toggleFavorite(int $id): void
    {
        $doc = Auth::user()->documents()->findOrFail($id);
        $doc->update(['is_favorite' => !$doc->is_favorite]);
    }

    public function deleteDocument(int $id): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $doc = $user->documents()->withTrashed()->findOrFail($id);

        if ($this->view === 'trash') {
            Storage::disk('public')->delete($doc->path);
            $user->reduceStorageUsage($doc->size_bytes);
            $doc->forceDelete();
            $this->successMessage = 'Arquivo removido definitivamente.';
        } else {
            $doc->delete();
            $this->successMessage = 'Arquivo movido para a lixeira.';
        }
    }

    public function restoreDocument(int $id): void
    {
        Auth::user()->documents()->onlyTrashed()->findOrFail($id)->restore();
        $this->successMessage = 'Arquivo restaurado com sucesso.';
    }

    public function setView(string $view): void
    {
        $this->view = $view;
        $this->currentFolderId = null;
        $this->search = '';
    }

    public function selectFolder(?int $id): void
    {
        $this->currentFolderId = $id;
        $this->view = 'all';
    }

    public function getIcon(string $mime): string
    {
        if (str_contains($mime, 'image'))                           return 'photo';
        if (str_contains($mime, 'video'))                           return 'video-camera';
        if (str_contains($mime, 'pdf'))                             return 'document-text';
        if (str_contains($mime, 'zip') || str_contains($mime, 'rar')) return 'archive-box';
        if (str_contains($mime, 'audio'))                           return 'musical-note';
        return 'document';
    }

    public function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow   = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow   = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    public function getPercentage(): float
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->plan || $user->plan->storage_limit_bytes === 0) return 0;
        return min(($user->storage_used_bytes / $user->plan->storage_limit_bytes) * 100, 100);
    }

    public function setFilterType(string $type): void
    {
        $this->filterType = $type;
    }

    public function with(): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $query = Document::query()->where('user_id', $user->id);

        if ($this->view === 'trash') {
            $query->onlyTrashed();
        } elseif ($this->view === 'favorites') {
            $query->where('is_favorite', true);
        } else {
            // Se houver busca, ignorar a pasta atual para pesquisar globalmente
            if (empty($this->search)) {
                $query->where('folder_id', $this->currentFolderId);
            }
        }

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        if ($this->filterType !== 'all') {
            switch ($this->filterType) {
                case 'image':
                    $query->where('mime_type', 'like', 'image/%');
                    break;
                case 'video':
                    $query->where('mime_type', 'like', 'video/%');
                    break;
                case 'pdf':
                    $query->where('mime_type', 'like', '%pdf%');
                    break;
                case 'audio':
                    $query->where('mime_type', 'like', 'audio/%');
                    break;
                case 'archive':
                    $query->where(function ($q) {
                        $q->where('mime_type', 'like', '%zip%')
                          ->orWhere('mime_type', 'like', '%rar%')
                          ->orWhere('mime_type', 'like', '%tar%');
                    });
                    break;
            }
        }

        $folders = [];
        if ($this->view === 'all' && empty($this->search)) {
            $folders = $user->folders()
                ->where('parent_id', $this->currentFolderId)
                ->get();
        }

        return [
            'documents' => $query->latest()->get(),
            'folders'   => $folders,
            'breadcrumbs' => $this->getBreadcrumbs(),
        ];
    }

    protected function getBreadcrumbs(): array
    {
        $breadcrumbs = [];
        $current = $this->currentFolderId ? Folder::find($this->currentFolderId) : null;

        while ($current) {
            array_unshift($breadcrumbs, $current);
            $current = $current->parent;
        }

        return $breadcrumbs;
    }
};
?>

<div class="max-w-7xl mx-auto py-12 px-6">

    {{-- Loading Overlay de Upload --}}
    <div
        wire:loading
        wire:target="file"
        class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-zinc-900/70 backdrop-blur-sm"
    >
        <div class="bg-white dark:bg-zinc-900 rounded-3xl p-10 shadow-2xl flex flex-col items-center gap-5 border border-zinc-200 dark:border-zinc-800">
            <div class="relative size-16">
                <svg class="animate-spin size-16 text-indigo-600" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                    <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v4l4-4-4-4v4a12 12 0 00-12 12h4z"/>
                </svg>
            </div>
            <div class="text-center">
                <p class="text-lg font-black text-zinc-900 dark:text-white">Enviando arquivo...</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Aguarde, seu arquivo está sendo processado.</p>
            </div>
        </div>
    </div>

    {{-- Notificação de sucesso --}}
    @if($successMessage)
        <div
            x-data="{ show: true }"
            x-init="setTimeout(() => show = false, 3000)"
            x-show="show"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed bottom-6 right-6 z-50 flex items-center gap-3 px-5 py-4 bg-indigo-600 text-white text-sm font-bold rounded-2xl shadow-xl shadow-indigo-500/30"
        >
            <flux:icon icon="check-circle" class="size-5 shrink-0" />
            {{ $successMessage }}
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col gap-8 mb-12">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-1">
                <div class="flex items-center gap-3">
                    @if($currentFolderId)
                        <flux:button wire:click="selectFolder(null)" variant="ghost" size="sm" icon="arrow-left" class="text-zinc-400 hover:text-indigo-600 p-0" />
                    @endif
                    <h1 class="text-4xl font-black tracking-tight text-zinc-900 dark:text-white leading-none">Meu Cofre</h1>
                </div>
                <div 
                class="flex items-center gap-2 text-zinc-500 dark:text-zinc-400 font-medium tracking-tight"
                x-data="{ over: false }"
                @dragover.prevent="over = true"
                @dragleave="over = false"
                @drop="over = false; $wire.selectedDocumentIds.length > 0 ? $wire.moveSelectedDocuments(null) : $wire.moveDocument($event.dataTransfer.getData('docId'), null)"
                x-bind:class="over ? 'text-indigo-600 scale-105 transition-all' : ''"
            >
                <button wire:click="selectFolder(null)" class="hover:text-indigo-600 transition-colors">Início</button>
                @foreach($breadcrumbs as $breadcrumb)
                    <flux:icon icon="chevron-right" class="size-3" />
                    <button 
                        @dragover.prevent="over = true"
                        @dragleave="over = false"
                        @drop="over = false; $wire.selectedDocumentIds.length > 0 ? $wire.moveSelectedDocuments({{ $breadcrumb->id }}) : $wire.moveDocument($event.dataTransfer.getData('docId'), {{ $breadcrumb->id }})"
                        wire:click="selectFolder({{ $breadcrumb->id }})" 
                        class="hover:text-indigo-600 transition-colors"
                    >
                        {{ $breadcrumb->name }}
                    </button>
                @endforeach
            </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="relative group">
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        placeholder="Pesquisar em tudo..."
                        icon="magnifying-glass"
                        class="w-64"
                    />
                </div>
                <label class="cursor-pointer" wire:loading.attr="disabled" wire:target="file">
                    <input type="file" wire:model="file" id="file-upload" class="hidden">
                    <flux:button as="div" variant="primary" icon-leading="plus" class="bg-indigo-600 hover:bg-indigo-700 h-11 px-6 shadow-lg shadow-indigo-500/20">
                        <span wire:loading.remove wire:target="file">Novo Upload</span>
                        <span wire:loading wire:target="file">Enviando...</span>
                    </flux:button>
                </label>
            </div>
        </div>

        {{-- Filtros de Extensão --}}
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-2">
                <flux:button wire:click="setFilterType('all')" size="sm" variant="{{ $filterType === 'all' ? 'primary' : 'ghost' }}" class="{{ $filterType === 'all' ? 'bg-indigo-600' : '' }}">Todos</flux:button>
                <flux:button wire:click="setFilterType('image')" size="sm" variant="{{ $filterType === 'image' ? 'primary' : 'ghost' }}" icon="photo" class="{{ $filterType === 'image' ? 'bg-indigo-600' : '' }}">Imagens</flux:button>
                <flux:button wire:click="setFilterType('video')" size="sm" variant="{{ $filterType === 'video' ? 'primary' : 'ghost' }}" icon="video-camera" class="{{ $filterType === 'video' ? 'bg-indigo-600' : '' }}">Vídeos</flux:button>
                <flux:button wire:click="setFilterType('pdf')" size="sm" variant="{{ $filterType === 'pdf' ? 'primary' : 'ghost' }}" icon="document-text" class="{{ $filterType === 'pdf' ? 'bg-indigo-600' : '' }}">PDFs</flux:button>
                <flux:button wire:click="setFilterType('audio')" size="sm" variant="{{ $filterType === 'audio' ? 'primary' : 'ghost' }}" icon="musical-note" class="{{ $filterType === 'audio' ? 'bg-indigo-600' : '' }}">Áudios</flux:button>
                <flux:button wire:click="setFilterType('archive')" size="sm" variant="{{ $filterType === 'archive' ? 'primary' : 'ghost' }}" icon="archive-box" class="{{ $filterType === 'archive' ? 'bg-indigo-600' : '' }}">Arquivados</flux:button>
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center bg-zinc-100 dark:bg-zinc-800 rounded-lg p-1">
                    <button 
                        wire:click="$set('displayMode', 'grid')" 
                        class="p-1 px-2 rounded-md transition-all {{ $displayMode === 'grid' ? 'bg-white dark:bg-zinc-700 shadow-sm text-indigo-600' : 'text-zinc-500 hover:text-zinc-700' }}"
                        title="Ver em Grade"
                    >
                        <flux:icon icon="squares-2x2" class="size-4" />
                    </button>
                    <button 
                        wire:click="$set('displayMode', 'list')" 
                        class="p-1 px-2 rounded-md transition-all {{ $displayMode === 'list' ? 'bg-white dark:bg-zinc-700 shadow-sm text-indigo-600' : 'text-zinc-500 hover:text-zinc-700' }}"
                        title="Ver em Lista"
                    >
                        <flux:icon icon="list-bullet" class="size-4" />
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Dashboard Content --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        {{-- Sidebar --}}
        <x-sidebar-nav :$currentFolderId :$view :$isCreatingFolder :$search />

        {{-- Main Grid Area --}}
        <div 
            class="lg:col-span-3 min-h-[500px] relative transition-all"
            x-data="{ isDragging: false }"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="
                isDragging = false; 
                if ($event.dataTransfer.files.length > 0) {
                    $wire.upload('file', $event.dataTransfer.files[0])
                }
            "
        >
            {{-- Overlay de Drop Visual --}}
            <div 
                x-show="isDragging" 
                class="absolute inset-0 z-30 bg-indigo-600/10 backdrop-blur-sm border-4 border-dashed border-indigo-500 rounded-3xl flex flex-col items-center justify-center animate-in fade-in duration-200"
            >
                <div class="bg-white dark:bg-zinc-900 p-8 rounded-full shadow-2xl scale-110">
                    <flux:icon icon="cloud-arrow-up" variant="solid" class="size-12 text-indigo-600 animate-bounce" />
                </div>
                <span class="mt-6 text-xl font-black text-indigo-600">Solte para fazer o Upload</span>
            </div>

            {{-- Erro de upload --}}
            @error('file')
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-sm font-bold rounded-2xl border border-red-100 dark:border-red-800 flex items-center gap-3">
                    <flux:icon icon="exclamation-circle" class="size-5 shrink-0" />
                    {{ $message }}
                </div>
            @enderror

            @if($documents->isEmpty() && empty($folders))
                <div class="group relative p-20 border-2 border-dashed border-zinc-200 dark:border-zinc-800 rounded-3xl flex flex-col items-center justify-center text-center transition-all hover:border-indigo-300 dark:hover:border-indigo-800 hover:bg-indigo-50/50 dark:hover:bg-indigo-900/5">
                    <div class="size-20 bg-zinc-50 dark:bg-zinc-900 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform shadow-sm">
                        <flux:icon icon="{{ $view === 'trash' ? 'trash' : ($view === 'favorites' ? 'star' : 'document') }}" class="size-10 text-zinc-300 dark:text-zinc-700" />
                    </div>
                    <h3 class="text-xl font-black text-zinc-900 dark:text-white mb-2">
                        @if($search) Nenhum resultado para "{{ $search }}"
                        @elseif($view === 'trash') Lixeira vazia
                        @elseif($view === 'favorites') Nenhum favorito
                        @else Seu cofre está vazio
                        @endif
                    </h3>
                    <p class="max-w-xs mx-auto text-sm text-zinc-500 dark:text-zinc-400 font-medium mb-6">
                        @if($search) Tente pesquisar por outros termos.
                        @elseif($view === 'trash') Arquivos excluídos aparecerão aqui.
                        @elseif($view === 'favorites') Marque seus arquivos importantes com uma estrela.
                        @else Comece enviando seus primeiros arquivos ou criando pastas.
                        @endif
                    </p>
                    @if($view === 'all' && !$search)
                        <label class="cursor-pointer">
                            <input type="file" wire:model="file" class="hidden">
                            <flux:button size="sm" variant="ghost" as="div" class="text-indigo-600 font-black hover:bg-indigo-50">Fazer primeiro upload</flux:button>
                        </label>
                    @endif
                </div>
            @else
                @if($displayMode === 'grid')
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        {{-- Folders --}}
                        @foreach($folders as $folder)
                            <x-folder-card :$folder :$currentFolderId :$selectedDocumentIds />
                        @endforeach

                        {{-- Documents --}}
                        @foreach($documents as $doc)
                            <x-file-card :$doc :$selectedDocumentIds :$view />
                        @endforeach
                    </div>
                @else
                    <div class="bg-white dark:bg-zinc-900 rounded-3xl border border-zinc-200 dark:border-zinc-800 overflow-hidden shadow-sm">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-zinc-50/50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-800">
                                    <th class="py-4 pl-4 pr-3 text-[10px] font-black uppercase tracking-widest text-zinc-400">Nome</th>
                                    <th class="py-4 px-3 text-[10px] font-black uppercase tracking-widest text-zinc-400">Tipo</th>
                                    <th class="py-4 px-3 text-[10px] font-black uppercase tracking-widest text-zinc-400">Tamanho</th>
                                    <th class="py-4 px-3 text-[10px] font-black uppercase tracking-widest text-zinc-400">Data</th>
                                    <th class="py-4 pl-3 pr-4 text-right"></th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Folders Row --}}
                                @foreach($folders as $folder)
                                    <x-folder-row :$folder :$selectedDocumentIds />
                                @endforeach

                                {{-- Documents Row --}}
                                @foreach($documents as $doc)
                                    <x-file-row :$doc :$selectedDocumentIds :$view />
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @endif
        </div>
    </div>
    <x-selection-bar :$selectedDocumentIds :$view :$currentFolderId />
</div>