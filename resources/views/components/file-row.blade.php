@props(['doc', 'selectedDocumentIds', 'view'])

<tr 
    wire:key="doc-row-{{ $doc->id }}"
    class="group hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors border-b border-zinc-100 dark:border-zinc-800 {{ in_array($doc->id, $selectedDocumentIds) ? 'bg-indigo-50/50 dark:bg-indigo-900/10' : '' }}"
>
    <td class="py-4 pl-4 pr-3">
        <div class="flex items-center gap-3">
            <input 
                type="checkbox" 
                wire:click.stop="toggleSelection({{ $doc->id }})" 
                {{ in_array($doc->id, $selectedDocumentIds) ? 'checked' : '' }}
                class="size-4 rounded border-zinc-300 dark:border-zinc-700 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
            >
            <flux:icon icon="{{ $this->getIcon($doc->mime_type) }}" class="size-6 text-zinc-400" />
            <span class="text-sm font-bold text-zinc-700 dark:text-zinc-300 truncate max-w-[200px]">{{ $doc->name }}</span>
            @if($doc->is_favorite)
                <flux:icon icon="star" variant="solid" class="size-3 text-yellow-400" />
            @endif
        </div>
    </td>
    <td class="py-4 px-3 text-sm text-zinc-500 font-medium uppercase tracking-wider text-[10px]">{{ explode('/', $doc->mime_type)[1] ?? 'Arquivo' }}</td>
    <td class="py-4 px-3 text-sm text-zinc-500 font-medium">{{ $this->formatBytes($doc->size_bytes) }}</td>
    <td class="py-4 px-3 text-sm text-zinc-500 font-medium">{{ $doc->created_at->format('d/m/Y H:i') }}</td>
    <td class="py-4 pl-3 pr-4 text-right">
        <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
            @if($doc->trashed())
                <flux:button wire:click="restoreDocument({{ $doc->id }})" variant="ghost" size="xs" icon="arrow-path" class="text-green-600" />
                <flux:button wire:click="deleteDocument({{ $doc->id }})" variant="ghost" size="xs" icon="trash" class="text-red-600" />
            @else
                <flux:button wire:click="toggleFavorite({{ $doc->id }})" variant="ghost" size="xs" icon="star" class="{{ $doc->is_favorite ? 'text-yellow-400' : 'text-zinc-400' }}" />
                <flux:button href="{{ asset('storage/' . $doc->path) }}" target="_blank" variant="ghost" size="xs" icon="eye" />
                <flux:button wire:click="deleteDocument({{ $doc->id }})" variant="ghost" size="xs" icon="trash" class="text-zinc-400 hover:text-red-500" />
            @endif
        </div>
    </td>
</tr>
