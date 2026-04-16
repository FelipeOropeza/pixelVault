@props(['folder', 'selectedDocumentIds'])

<tr 
    wire:key="folder-row-{{ $folder->id }}"
    wire:click="selectFolder({{ $folder->id }})"
    class="group cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors border-b border-zinc-100 dark:border-zinc-800"
>
    <td class="py-4 pl-4 pr-3">
        <div class="flex items-center gap-3">
            <flux:icon icon="folder" variant="solid" class="size-6 text-indigo-500/40" />
            <span class="text-sm font-bold text-zinc-700 dark:text-zinc-300">{{ $folder->name }}</span>
        </div>
    </td>
    <td class="py-4 px-3 text-sm text-zinc-500 font-medium">Pasta</td>
    <td class="py-4 px-3 text-sm text-zinc-500 font-medium">{{ $folder->documents_count ?? $folder->documents()->count() }} itens</td>
    <td class="py-4 px-3 text-sm text-zinc-500 font-medium">-</td>
    <td class="py-4 pl-3 pr-4 text-right">
        <flux:button 
            wire:click.stop="deleteFolder({{ $folder->id }})" 
            variant="ghost" 
            size="xs" 
            icon="trash" 
            class="text-zinc-400 hover:text-red-500 opacity-0 group-hover:opacity-100" 
        />
    </td>
</tr>
