@props(['folder', 'currentFolderId', 'selectedDocumentIds'])

<div
    wire:key="folder-{{ $folder->id }}"
    wire:click="selectFolder({{ $folder->id }})"
    x-data="{ over: false }"
    @dragover.prevent="over = true"
    @dragleave="over = false"
    @drop="over = false; $wire.selectedDocumentIds.length > 0 ? $wire.moveSelectedDocuments({{ $folder->id }}) : $wire.moveDocument($event.dataTransfer.getData('docId'), {{ $folder->id }})"
    class="group cursor-pointer aspect-square bg-white dark:bg-zinc-900 rounded-3xl p-6 flex flex-col items-center justify-center border border-zinc-200 dark:border-zinc-800 shadow-sm transition-all hover:shadow-xl hover:shadow-indigo-500/5 hover:border-indigo-200"
    x-bind:class="over ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-900/10 scale-105' : ''"
>
    <div class="relative group/folder">
        <flux:icon icon="folder" class="size-16 text-indigo-100 dark:text-indigo-900/30 group-hover:text-indigo-200 transition-colors" variant="solid" />
        <flux:icon icon="folder" class="absolute inset-0 size-16 text-indigo-500/10" />
        
        {{-- Botão Delete da Pasta --}}
        <button 
            wire:click.stop="deleteFolder({{ $folder->id }})" 
            class="absolute -top-2 -right-2 size-6 bg-white dark:bg-zinc-800 rounded-lg shadow-lg border border-zinc-200 dark:border-zinc-700 flex items-center justify-center text-zinc-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-all hover:scale-110"
            title="Excluir Pasta"
        >
            <flux:icon icon="x-mark" class="size-3" />
        </button>
    </div>
    <span class="mt-4 text-sm font-bold text-zinc-700 dark:text-zinc-300 text-center truncate w-full">{{ $folder->name }}</span>
    <span class="text-[10px] text-zinc-400 mt-1 font-medium">{{ $folder->documents_count ?? $folder->documents()->count() }} arquivos</span>
</div>
