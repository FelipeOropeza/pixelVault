<?php // resources/views/pages/⚡dashboard.blade.php

use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Livewire\Component;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    use WithFileUploads, WithPagination;
    use \App\Traits\HasFileHelpers;

    public array $selectedDocumentIds = [];
    public string $displayMode = 'grid'; // grid, list
    public string $successMessage = '';

    #[\Livewire\Attributes\Url]
    public string $view = 'all';
    
    #[\Livewire\Attributes\Url]
    public string $search = '';
    
    #[\Livewire\Attributes\Url]
    public ?int $currentFolderId = null;
    
    #[\Livewire\Attributes\Url]
    public string $filterType = 'all';

    #[\Livewire\Attributes\On('dashboard-refresh')]
    public function refresh($view = null, $search = null, $folderId = null, $filterType = null): void
    {
        if ($view !== null) $this->view = $view;
        if ($search !== null) $this->search = $search;
        if ($folderId !== null || $view !== null) $this->currentFolderId = $folderId;
        if ($filterType !== null) $this->filterType = $filterType;
        
        $this->resetPage();
    }

    public function selectFolder(?int $id): void
    {
        $this->currentFolderId = $id;
        $this->view = 'all';
        $this->resetPage();
    }

    #[\Livewire\Attributes\On('notify')]
    public function notify(string $message): void
    {
        $this->successMessage = $message;
    }

    #[\Livewire\Attributes\On('toggle-selection')]
    public function handleToggleSelection(int $id, string $type = 'file'): void
    {
        $this->toggleSelection($id, $type);
    }

    #[\Livewire\Attributes\On('drop-on-folder')]
    public function handleDropOnFolder(int $folderId, string $docId): void
    {
        if (!empty($this->selectedDocumentIds)) {
            $this->moveSelectedDocuments($folderId);
        } elseif ($docId) {
            $this->moveDocument((int) $docId, $folderId);
        }
    }

    public function toggleSelection(int $id, string $type = 'file'): void
    {
        $key = "$type:$id";
        if (in_array($key, $this->selectedDocumentIds)) {
            $this->selectedDocumentIds = array_values(array_diff($this->selectedDocumentIds, [$key]));
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

        if ($this->view === 'trash') {
            $totalFreed = 0;
            foreach ($docs as $doc) {
                Storage::disk('public')->delete($doc->path);
                $totalFreed += $doc->size_bytes;
            }
            if ($totalFreed > 0) {
                $user->reduceStorageUsage($totalFreed);
            }
            $user->documents()->whereIn('id', $fileIds)->forceDelete();
        } else {
            $user->documents()->whereIn('id', $fileIds)->delete();
        }

        if ($folderIds->isNotEmpty()) {
            if ($this->view === 'trash') {
                $user->folders()->whereIn('id', $folderIds)->forceDelete(); // Pastas nem deveriam estar na lixeira, mas caso estejam.
            } else {
                // Soft delete manual dos documentos das pastas selecionadas
                foreach ($folders as $folder) {
                    $folder->documents()->update(['deleted_at' => now()]);
                }
                $user->folders()->whereIn('id', $folderIds)->delete();
            }
        }

        $this->clearSelection();
        $this->dispatch('notify', ($docs->count() + $folders->count()) . ' itens processados.');
        $this->dispatch('dashboard-refresh');
    }

    public function favoriteSelected(): void
    {
        $fileIds = collect($this->selectedDocumentIds)->filter(fn($k) => str_starts_with($k, 'file:'))->map(fn($k) => (int) explode(':', $k)[1]);
        Auth::user()->documents()->whereIn('id', $fileIds)->update(['is_favorite' => true]);
        $this->clearSelection();
        $this->dispatch('notify', 'Arquivos favoritados com sucesso.');
        $this->dispatch('dashboard-refresh');
    }

    public function restoreSelected(): void
    {
        $fileIds = collect($this->selectedDocumentIds)->filter(fn($k) => str_starts_with($k, 'file:'))->map(fn($k) => (int) explode(':', $k)[1]);
        Auth::user()->documents()->onlyTrashed()->whereIn('id', $fileIds)->restore();
        $this->clearSelection();
        $this->dispatch('notify', 'Arquivos restaurados com sucesso.');
        $this->dispatch('dashboard-refresh');
    }

    public function moveSelectedDocuments(?int $targetFolderId): void
    {
        if (empty($this->selectedDocumentIds)) return;

        $fileIds = collect($this->selectedDocumentIds)->filter(fn($k) => str_starts_with($k, 'file:'))->map(fn($k) => (int) explode(':', $k)[1]);
        $folderIds = collect($this->selectedDocumentIds)->filter(fn($k) => str_starts_with($k, 'folder:'))->map(fn($k) => (int) explode(':', $k)[1]);

        if ($fileIds->isNotEmpty()) {
            Auth::user()->documents()->withTrashed()->whereIn('id', $fileIds)->update(['folder_id' => $targetFolderId]);
        }

        if ($folderIds->isNotEmpty()) {
            // Evitar mover uma pasta para dentro dela mesma
            if ($targetFolderId && $folderIds->contains($targetFolderId)) {
                $this->addError('general', 'Não é possível mover uma pasta para dentro de si mesma.');
                return;
            }
            Auth::user()->folders()->whereIn('id', $folderIds)->update(['parent_id' => $targetFolderId]);
        }
        
        $count = count($this->selectedDocumentIds);
        $this->clearSelection();
        $this->dispatch('notify', "$count itens movidos com sucesso!");
        $this->dispatch('dashboard-refresh');
    }

    public function deleteFolder(int $id, bool $force = false): void
    {
        $folder = Auth::user()->folders()->withCount('documents')->findOrFail($id);

        if ($folder->documents_count > 0 && !$force) {
            $this->js("if(confirm('Esta pasta contém {$folder->documents_count} arquivos. Deseja excluir a pasta e mover todos os arquivos para a lixeira?')) { \$wire.deleteFolder($id, true) }");
            return;
        }

        if ($folder->documents_count > 0) {
            $folder->documents()->update(['deleted_at' => now()]);
        }

        $folder->delete();
        
        // Remove da seleção se estiver lá
        $key = "folder:$id";
        if (($keyIndex = array_search($key, $this->selectedDocumentIds)) !== false) {
            unset($this->selectedDocumentIds[$keyIndex]);
            $this->selectedDocumentIds = array_values($this->selectedDocumentIds);
        }

        $this->dispatch('notify', 'Pasta excluída com sucesso!');
        
        if ($this->currentFolderId === $id) {
            $this->currentFolderId = null;
        }
        $this->dispatch('dashboard-refresh');
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
            $this->dispatch('notify', 'Arquivo removido definitivamente.');
        } else {
            $doc->delete();
            $this->dispatch('notify', 'Arquivo movido para a lixeira.');
        }

        // Remove da seleção se estiver lá
        $key = "file:$id";
        if (($keyIndex = array_search($key, $this->selectedDocumentIds)) !== false) {
            unset($this->selectedDocumentIds[$keyIndex]);
            $this->selectedDocumentIds = array_values($this->selectedDocumentIds);
        }

        $this->dispatch('dashboard-refresh');
    }

    public function restoreDocument(int $id): void
    {
        Auth::user()->documents()->onlyTrashed()->findOrFail($id)->restore();
        $this->dispatch('notify', 'Arquivo restaurado com sucesso.');
        $this->dispatch('dashboard-refresh');
    }

    public function moveDocument(int $docId, ?int $targetFolderId): void
    {
        $doc = Auth::user()->documents()->withTrashed()->findOrFail($docId);
        $doc->update(['folder_id' => $targetFolderId]);
        $this->dispatch('notify', 'Arquivo movido com sucesso!');
        $this->dispatch('dashboard-refresh');
    }



    public function downloadDocument(int $id)
    {
        $doc = Auth::user()->documents()->withTrashed()->findOrFail($id);
        
        $path = Storage::disk('public')->path($doc->path);

        if (!file_exists($path)) {
            $this->addError('general', 'Arquivo não encontrado no servidor.');
            return null;
        }

        return response()->download($path, $doc->name);
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

    public function setFilterType(string $type): void
    {
        $this->filterType = $type;
        $this->resetPage();
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
            'documents' => $query->latest()->paginate(24),
            'folders'   => $folders,
        ];
    }
};
?>

<div class="max-w-7xl mx-auto py-12 px-6">


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

    <livewire:dashboard.toolbar 
        :$currentFolderId 
        :$search 
        :$filterType 
        :$displayMode 
    />

    {{-- Dashboard Content --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        {{-- Sidebar --}}
        <livewire:dashboard.sidebar 
            :$currentFolderId 
            :$view 
            :$search 
        />

        {{-- Main Grid Area --}}
        <div 
            class="lg:col-span-3 min-h-[500px] relative transition-all"
            x-data="{ isDragging: false }"
            @dragover.prevent="if ($event.dataTransfer.types.includes('Files')) isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="
                isDragging = false; 
                if ($event.dataTransfer.files.length > 0) {
                    $dispatch('trigger-upload', { files: $event.dataTransfer.files })
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

                    {{-- Paginação --}}
                    <div class="mt-8">
                        {{ $documents->links() }}
                    </div>
                @endif

            <x-selection-bar :$selectedDocumentIds :$view :$currentFolderId />

    {{-- Modais Isolados (Islands) --}}
    <livewire:dashboard.image-preview />
    <livewire:dashboard.rename-modal />

</div>
