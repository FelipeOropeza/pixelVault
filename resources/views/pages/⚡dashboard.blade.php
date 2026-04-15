<?php // resources/views/pages/⚡dashboard.blade.php

use Livewire\WithFileUploads;
use Livewire\Component;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    use WithFileUploads;

    public $file;

    public function updatedFile()
    {
        $this->validate([
            'file' => 'required|max:20480', // Max 20MB
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $size = $this->file->getSize();

        if (!$user->hasAvailableStorage($size)) {
            $this->addError('file', 'Você não tem espaço suficiente no seu plano. Faça um upgrade!');
            $this->file = null;
            return;
        }

        $path = $this->file->store('documents/' . $user->id, 'public');

        Document::create([
            'user_id' => $user->id,
            'name' => $this->file->getClientOriginalName(),
            'path' => $path,
            'size_bytes' => $size,
            'mime_type' => $this->file->getMimeType(),
        ]);

        $user->addStorageUsage($size);
        $this->file = null;

        $this->js('alert("Documento enviado com sucesso!")');
    }

    public function deleteDocument($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $doc = $user->documents()->findOrFail($id);
        Storage::disk('public')->delete($doc->path);
        
        $user->reduceStorageUsage($doc->size_bytes);
        $doc->delete();
        
        $this->js('alert("Documento excluído.")');
    }

    public function getIcon($mime)
    {
        if (str_contains($mime, 'image')) return 'photo';
        if (str_contains($mime, 'video')) return 'video-camera';
        if (str_contains($mime, 'pdf')) return 'document-text';
        if (str_contains($mime, 'zip') || str_contains($mime, 'rar')) return 'archive-box';
        return 'document';
    }

    public function with()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        return [
            'documents' => $user->documents()->latest()->get(),
        ];
    }

    public function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    public function getPercentage()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->subscription) return 0;
        return ($user->storage_used_bytes / $user->subscription->storage_limit_bytes) * 100;
    }
};
?>

<div class="max-w-7xl mx-auto py-12 px-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-12 animate-fade-in">
        <div class="space-y-1">
            <h1 class="text-4xl font-black tracking-tight text-zinc-900 dark:text-white leading-none">Meu Cofre</h1>
            <p class="text-zinc-500 dark:text-zinc-400 font-medium tracking-tight">Bem-vindo de volta, {{ auth()->user()->name }}!</p>
        </div>

        <div class="flex items-center gap-3">
            <flux:button variant="ghost" icon="magnifying-glass" />
            <label class="cursor-pointer">
                <input type="file" wire:model="file" class="hidden">
                <flux:button as="div" variant="primary" icon-leading="plus" class="bg-indigo-600 hover:bg-indigo-700 h-11 px-6 shadow-lg shadow-indigo-500/20">
                    Novo Upload
                </flux:button>
            </label>
        </div>
    </div>

    <!-- Dashboard Content -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Sidebar Navigation -->
        <aside class="lg:col-span-1 space-y-6">
            <div class="p-6 bg-white/50 dark:bg-zinc-900/50 backdrop-blur-xl rounded-3xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
                <nav class="space-y-1">
                    <flux:navlist>
                        <flux:navlist.item href="#" icon="squares-2x2" current>Todos os Arquivos</flux:navlist.item>
                        <flux:navlist.item href="#" icon="star">Favoritos</flux:navlist.item>
                        <flux:navlist.item href="#" icon="clock">Recentes</flux:navlist.item>
                        <flux:navlist.item href="#" icon="trash">Lixeira</flux:navlist.item>
                    </flux:navlist>
                </nav>
            </div>

            <div class="px-2">
                <h3 class="text-xs font-black uppercase tracking-widest text-zinc-400 dark:text-zinc-500 mb-4 px-4">Espaço em Nuvem</h3>
                <div class="p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-2xl border border-zinc-200 dark:border-zinc-800">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-zinc-600 dark:text-zinc-400">
                            {{ auth()->user()->subscription?->name ?? 'Sem Plano' }}
                        </span>
                        <span class="text-xs font-black text-indigo-600">
                            {{ round($this->getPercentage(), 1) }}%
                        </span>
                    </div>
                    
                    <div class="w-full bg-zinc-200 dark:bg-zinc-700 h-1.5 rounded-full overflow-hidden mb-3">
                        <div class="bg-indigo-600 h-full rounded-full transition-all duration-500" @style(['width: ' . $this->getPercentage() . '%'])></div>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-medium text-zinc-500">
                            {{ $this->formatBytes(auth()->user()->storage_used_bytes) }} de {{ $this->formatBytes(auth()->user()->subscription?->storage_limit_bytes ?? 0) }}
                        </span>
                        <flux:link href="/plans" icon="arrow-up-circle" class="text-[10px] font-black text-indigo-600">Upgrade</flux:link>
                    </div>
                </div>
            </div>

            <div class="px-2 pt-4">
                <h3 class="text-xs font-black uppercase tracking-widest text-zinc-400 dark:text-zinc-500 mb-4 px-4 font-black">Minhas Pastas</h3>
                <div class="space-y-1">
                    <flux:button variant="ghost" class="w-full justify-start text-zinc-500" icon-leading="folder">Documentos</flux:button>
                    <flux:button variant="ghost" class="w-full justify-start text-zinc-500" icon-leading="folder">Trabalho</flux:button>
                    <flux:button variant="ghost" class="w-full justify-start text-zinc-400" icon-leading="plus">Nova Pasta</flux:button>
                </div>
            </div>
        </aside>

        <!-- Main Grid Area -->
        <div class="lg:col-span-3">
            @if($documents->isEmpty())
                <div class="group relative p-20 border-2 border-dashed border-zinc-200 dark:border-zinc-800 rounded-3xl flex flex-col items-center justify-center text-center transition-all hover:border-indigo-300 dark:hover:border-indigo-900 hover:bg-indigo-50/50 dark:hover:bg-indigo-900/5">
                    <div class="size-20 bg-zinc-50 dark:bg-zinc-900 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform shadow-sm">
                        <flux:icon icon="document" class="size-10 text-zinc-300 dark:text-zinc-700" />
                    </div>
                    
                    <h3 class="text-xl font-black text-zinc-900 dark:text-white mb-2 underline decoration-indigo-500/30">Seu cofre está vazio</h3>
                    <p class="max-w-xs mx-auto text-sm text-zinc-500 dark:text-zinc-400 font-medium mb-6">Comece enviando seus primeiros arquivos para organizar seu portfólio digital.</p>
                    
                    <label class="cursor-pointer">
                        <input type="file" wire:model="file" class="hidden">
                        <flux:button size="sm" variant="ghost" as="div" class="text-indigo-600 font-black hover:bg-indigo-50">Fazer primeiro upload</flux:button>
                    </label>
                </div>
            @else
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    @foreach($documents as $doc)
                        <div wire:key="doc-{{ $doc->id }}" class="group relative aspect-square bg-white dark:bg-zinc-900 rounded-3xl overflow-hidden border border-zinc-200 dark:border-zinc-800 shadow-sm transition-all hover:shadow-xl hover:shadow-indigo-500/5">
                            @if(str_contains($doc->mime_type, 'image'))
                                <img src="{{ asset('storage/' . $doc->path) }}" class="w-full h-full object-cover transition-transform group-hover:scale-110" alt="{{ $doc->name }}">
                            @else
                                <div class="w-full h-full flex flex-col items-center justify-center p-6 bg-zinc-50 dark:bg-zinc-800/30 group-hover:bg-indigo-50/50 transition-colors">
                                    <flux:icon icon="{{ $this->getIcon($doc->mime_type) }}" class="size-12 text-zinc-300 dark:text-zinc-600 mb-3" />
                                    <span class="text-[10px] font-black text-zinc-400 uppercase tracking-widest text-center truncate w-full px-2">{{ $doc->name }}</span>
                                </div>
                            @endif

                            <div class="absolute inset-x-0 bottom-0 p-4 translate-y-full group-hover:translate-y-0 transition-transform bg-gradient-to-t from-black/80 to-transparent flex items-center justify-between gap-2">
                                <span class="text-[10px] font-bold text-white truncate flex-1">{{ $doc->name }}</span>
                                <div class="flex items-center gap-1">
                                    <a href="{{ asset('storage/' . $doc->path) }}" target="_blank">
                                        <flux:button variant="ghost" size="sm" icon="eye" class="text-white hover:bg-white/20" />
                                    </a>
                                    <flux:button wire:click="deleteDocument({{ $doc->id }})" wire:confirm="Tem certeza?" variant="ghost" size="sm" icon="trash" class="text-white hover:bg-red-500/50" />
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @error('file')
                <div class="mt-4 p-4 bg-red-50 text-red-600 text-sm font-bold rounded-2xl border border-red-100">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>
</div>