@props(['folder', 'currentFolderId', 'selectedDocumentIds'])

<div
    wire:key="folder-{{ $folder->id }}"
    x-data="{ over: false }"
    @dragover.prevent="over = true"
    @dragleave="over = false"
    @drop.prevent="over = false; $dispatch('drop-on-folder', { folderId: {{ $folder->id }}, docId: $event.dataTransfer.getData('docId') })"
    class="group relative cursor-pointer aspect-square bg-white dark:bg-zinc-900 rounded-3xl p-6 flex flex-col items-center justify-center border {{ in_array('folder:'.$folder->id, $selectedDocumentIds) ? 'border-indigo-500 ring-4 ring-indigo-500/10' : 'border-zinc-200 dark:border-zinc-800' }} shadow-sm transition-all hover:shadow-xl hover:shadow-indigo-500/10"
    x-bind:class="over ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-900/10 scale-105' : ''"
    @click="$dispatch('toggle-selection', { id: {{ $folder->id }}, type: 'folder' })"
    @dblclick="$wire.selectFolder({{ $folder->id }})"
>
    {{-- Checkbox de Seleção --}}
    <div class="absolute top-3 left-3 z-20 opacity-0 group-hover:opacity-100 transition-opacity {{ in_array('folder:'.$folder->id, $selectedDocumentIds) ? 'opacity-100' : '' }}">
        <input 
            type="checkbox" 
            wire:key="checkbox-folder-{{ $folder->id }}-{{ in_array('folder:'.$folder->id, $selectedDocumentIds) ? '1' : '0' }}"
            @click.stop="$dispatch('toggle-selection', { id: {{ $folder->id }}, type: 'folder' })"
            {{ in_array('folder:'.$folder->id, $selectedDocumentIds) ? 'checked' : '' }}
            class="size-5 rounded-lg border-zinc-300 dark:border-zinc-700 text-indigo-600 focus:ring-indigo-500 cursor-pointer shadow-sm"
        >
    </div>
    <div class="relative group/folder">
        <flux:icon icon="folder" class="size-16 text-indigo-100 dark:text-indigo-900/30 group-hover:text-indigo-200 transition-colors" variant="solid" />
        <flux:icon icon="folder" class="absolute inset-0 size-16 text-indigo-500/10" />
        
        {{-- Botões de Ação --}}
        <div class="absolute -top-2 -right-2 flex flex-col gap-1 opacity-0 group-hover:opacity-100 transition-all">
            <button 
                x-on:click.stop="$dispatch('edit-name', { id: {{ $folder->id }}, type: 'folder' })" 
                class="size-6 bg-white dark:bg-zinc-800 rounded-lg shadow-lg border border-zinc-200 dark:border-zinc-700 flex items-center justify-center text-zinc-400 hover:text-indigo-600 hover:scale-110"
                title="Renomear Pasta"
            >
                <flux:icon icon="pencil-square" class="size-3" />
            </button>
            <button 
                wire:click.stop="deleteFolder({{ $folder->id }})" 
                class="size-6 bg-white dark:bg-zinc-800 rounded-lg shadow-lg border border-zinc-200 dark:border-zinc-700 flex items-center justify-center text-zinc-400 hover:text-red-500 hover:scale-110"
                title="Excluir Pasta"
            >
                <flux:icon icon="x-mark" class="size-3" />
            </button>
        </div>
    </div>
    <span @click.stop="$wire.selectFolder({{ $folder->id }})" class="mt-4 text-sm font-bold text-zinc-700 dark:text-zinc-300 text-center truncate w-full hover:text-indigo-600 transition-colors">{{ $folder->name }}</span>
    <span class="text-[10px] text-zinc-400 mt-1 font-medium">{{ $folder->documents_count ?? $folder->documents()->count() }} arquivos</span>
</div>
