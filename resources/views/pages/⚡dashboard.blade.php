<?php // resources/views/pages/⚡dashboard.blade.php

use Livewire\WithFileUploads;
use Livewire\Component;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    use WithFileUploads;

    public $file;
    public string $successMessage = '';

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
            'name'      => $this->file->getClientOriginalName(),
            'path'      => $path,
            'size_bytes'=> $size,
            'mime_type' => $this->file->getMimeType(),
        ]);

        $user->addStorageUsage($size);
        $this->file = null;
        $this->successMessage = 'Arquivo enviado com sucesso!';
    }

    public function deleteDocument(int $id): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $doc = $user->documents()->findOrFail($id);

        Storage::disk('public')->delete($doc->path);
        $user->reduceStorageUsage($doc->size_bytes);
        $doc->delete();

        $this->successMessage = 'Arquivo excluído com sucesso.';
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
        if (!$user->subscription || $user->subscription->storage_limit_bytes === 0) return 0;
        return min(($user->storage_used_bytes / $user->subscription->storage_limit_bytes) * 100, 100);
    }

    public function with(): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        return [
            'documents' => $user->documents()->latest()->get(),
        ];
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
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-12">
        <div class="space-y-1">
            <h1 class="text-4xl font-black tracking-tight text-zinc-900 dark:text-white leading-none">Meu Cofre</h1>
            <p class="text-zinc-500 dark:text-zinc-400 font-medium tracking-tight">Bem-vindo de volta, {{ auth()->user()->name }}!</p>
        </div>

        <div class="flex items-center gap-3">
            <flux:button variant="ghost" icon="magnifying-glass" />
            <label class="cursor-pointer" wire:loading.attr="disabled" wire:target="file">
                <input type="file" wire:model="file" id="file-upload" class="hidden">
                <flux:button as="div" variant="primary" icon-leading="plus" class="bg-indigo-600 hover:bg-indigo-700 h-11 px-6 shadow-lg shadow-indigo-500/20">
                    <span wire:loading.remove wire:target="file">Novo Upload</span>
                    <span wire:loading wire:target="file">Enviando...</span>
                </flux:button>
            </label>
        </div>
    </div>

    {{-- Dashboard Content --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        {{-- Sidebar --}}
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

            {{-- Espaço em Nuvem --}}
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
                        <div
                            class="h-full rounded-full transition-all duration-500 {{ $this->getPercentage() >= 90 ? 'bg-red-500' : 'bg-indigo-600' }}"
                            @style(['width: ' . $this->getPercentage() . '%'])
                        ></div>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-medium text-zinc-500">
                            {{ $this->formatBytes(auth()->user()->storage_used_bytes) }}
                            de
                            {{ $this->formatBytes(auth()->user()->subscription?->storage_limit_bytes ?? 0) }}
                        </span>
                        <flux:link href="/plans" icon="arrow-up-circle" class="text-[10px] font-black text-indigo-600">Upgrade</flux:link>
                    </div>
                </div>
            </div>

            {{-- Pastas --}}
            <div class="px-2 pt-4">
                <h3 class="text-xs font-black uppercase tracking-widest text-zinc-400 dark:text-zinc-500 mb-4 px-4">Minhas Pastas</h3>
                <div class="space-y-1">
                    <flux:button variant="ghost" class="w-full justify-start text-zinc-500" icon-leading="folder">Documentos</flux:button>
                    <flux:button variant="ghost" class="w-full justify-start text-zinc-500" icon-leading="folder">Trabalho</flux:button>
                    <flux:button variant="ghost" class="w-full justify-start text-zinc-400" icon-leading="plus">Nova Pasta</flux:button>
                </div>
            </div>
        </aside>

        {{-- Main Grid Area --}}
        <div class="lg:col-span-3">

            {{-- Erro de upload --}}
            @error('file')
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-sm font-bold rounded-2xl border border-red-100 dark:border-red-800 flex items-center gap-3">
                    <flux:icon icon="exclamation-circle" class="size-5 shrink-0" />
                    {{ $message }}
                </div>
            @enderror

            @if($documents->isEmpty())
                <div class="group relative p-20 border-2 border-dashed border-zinc-200 dark:border-zinc-800 rounded-3xl flex flex-col items-center justify-center text-center transition-all hover:border-indigo-300 dark:hover:border-indigo-800 hover:bg-indigo-50/50 dark:hover:bg-indigo-900/5">
                    <div class="size-20 bg-zinc-50 dark:bg-zinc-900 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform shadow-sm">
                        <flux:icon icon="document" class="size-10 text-zinc-300 dark:text-zinc-700" />
                    </div>
                    <h3 class="text-xl font-black text-zinc-900 dark:text-white mb-2">Seu cofre está vazio</h3>
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
                                    <span class="text-[9px] text-zinc-400 mt-1">{{ $this->formatBytes($doc->size_bytes) }}</span>
                                </div>
                            @endif

                            <div class="absolute inset-x-0 bottom-0 p-4 translate-y-full group-hover:translate-y-0 transition-transform bg-gradient-to-t from-black/80 to-transparent flex items-center justify-between gap-2">
                                <span class="text-[10px] font-bold text-white truncate flex-1">{{ $doc->name }}</span>
                                <div class="flex items-center gap-1">
                                    <a href="{{ asset('storage/' . $doc->path) }}" target="_blank">
                                        <flux:button variant="ghost" size="sm" icon="eye" class="text-white hover:bg-white/20" />
                                    </a>
                                    <flux:button
                                        wire:click="deleteDocument({{ $doc->id }})"
                                        wire:confirm="Tem certeza que deseja excluir este arquivo?"
                                        wire:loading.attr="disabled"
                                        variant="ghost"
                                        size="sm"
                                        icon="trash"
                                        class="text-white hover:bg-red-500/50"
                                    />
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>