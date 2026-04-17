@props(['folder', 'selectedDocumentIds'])

<tr 
    wire:key="folder-row-{{ $folder->id }}"
    x-data="{ over: false }"
    @dragover.prevent="over = true"
    @dragleave="over = false"
    @drop.prevent="over = false; $dispatch('drop-on-folder', { folderId: {{ $folder->id }}, docId: $event.dataTransfer.getData('docId') })"
    class="group cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors border-b border-zinc-100 dark:border-zinc-800 transition-all {{ in_array('folder:'.$folder->id, $selectedDocumentIds) ? 'bg-indigo-50/50 dark:bg-indigo-900/10' : '' }}"
    x-bind:class="over ? 'bg-indigo-50 dark:bg-indigo-900/20 shadow-inner' : ''"
>
    <td class="py-4 pl-4 pr-3">
        <div class="flex items-center gap-3">
            <input 
                type="checkbox" 
                wire:key="checkbox-folder-row-{{ $folder->id }}-{{ in_array('folder:'.$folder->id, $selectedDocumentIds) ? '1' : '0' }}"
                @click.stop="$dispatch('toggle-selection', { id: {{ $folder->id }}, type: 'folder' })"
                {{ in_array('folder:'.$folder->id, $selectedDocumentIds) ? 'checked' : '' }}
                class="size-4 rounded border-zinc-300 dark:border-zinc-700 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
            >
            <flux:icon icon="folder" variant="solid" class="size-6 text-indigo-500/40" />
            <span wire:click="selectFolder({{ $folder->id }})" class="text-sm font-bold text-zinc-700 dark:text-zinc-300 hover:text-indigo-600 transition-colors">{{ $folder->name }}</span>
        </div>
    </td>
    <td class="py-4 px-3 text-sm text-zinc-500 font-medium">Pasta</td>
    <td class="py-4 px-3 text-sm text-zinc-500 font-medium">{{ $folder->documents_count ?? $folder->documents()->count() }} itens</td>
    <td class="py-4 px-3 text-sm text-zinc-500 font-medium">-</td>
    <td class="py-4 pl-3 pr-4 text-right">
        <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
            <flux:button 
                x-on:click.stop="$dispatch('edit-name', { id: {{ $folder->id }}, type: 'folder' })" 
                variant="ghost" 
                size="xs" 
                icon="pencil-square" 
                class="text-zinc-400 hover:text-indigo-600" 
            />
            <flux:button 
                wire:click.stop="deleteFolder({{ $folder->id }})" 
                variant="ghost" 
                size="xs" 
                icon="trash" 
                class="text-zinc-400 hover:text-red-500" 
            />
        </div>
    </td>
</tr>
