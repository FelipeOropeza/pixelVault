@props(['doc', 'selectedDocumentIds', 'view'])

<div 
    wire:key="doc-{{ $doc->id }}" 
    draggable="true"
    @dragstart="event.dataTransfer.setData('docId', {{ $doc->id }})"
    class="group relative aspect-square bg-white dark:bg-zinc-900 rounded-3xl overflow-hidden border {{ in_array($doc->id, $selectedDocumentIds) ? 'border-indigo-500 ring-2 ring-indigo-500/20' : 'border-zinc-200 dark:border-zinc-800' }} shadow-sm transition-all hover:shadow-xl hover:shadow-indigo-500/5 cursor-grab active:cursor-grabbing"
>
    {{-- Checkbox de Seleção --}}
    <div class="absolute top-3 left-3 z-20 opacity-0 group-hover:opacity-100 transition-opacity {{ in_array($doc->id, $selectedDocumentIds) ? 'opacity-100' : '' }}">
        <input 
            type="checkbox" 
            wire:click.stop="toggleSelection({{ $doc->id }})" 
            {{ in_array($doc->id, $selectedDocumentIds) ? 'checked' : '' }}
            class="size-5 rounded-lg border-zinc-300 dark:border-zinc-700 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
        >
    </div>

    @if(str_contains($doc->mime_type, 'image'))
        <img src="{{ asset('storage/' . $doc->path) }}" class="w-full h-full object-cover transition-transform group-hover:scale-110 pointer-events-none" alt="{{ $doc->name }}">
    @else
        <div class="w-full h-full flex flex-col items-center justify-center p-6 bg-zinc-50 dark:bg-zinc-800/30 group-hover:bg-indigo-50/50 transition-colors">
            <flux:icon icon="{{ $this->getIcon($doc->mime_type) }}" class="size-12 text-zinc-300 dark:text-zinc-600 mb-3" />
            <span class="text-[10px] font-black text-zinc-400 uppercase tracking-widest text-center truncate w-full px-2">{{ $doc->name }}</span>
            <span class="text-[9px] text-zinc-400 mt-1">{{ $this->formatBytes($doc->size_bytes) }}</span>
        </div>
    @endif

    {{-- Favorite Badge --}}
    @if($doc->is_favorite)
        <div class="absolute top-3 right-3 z-10">
            <flux:icon icon="star" variant="solid" class="size-4 text-yellow-400 drop-shadow-md" />
        </div>
    @endif

    <div class="absolute inset-x-0 bottom-0 p-4 translate-y-full group-hover:translate-y-0 transition-transform bg-gradient-to-t from-black/80 to-transparent flex items-center justify-between gap-2">
        <span class="text-[10px] font-bold text-white truncate flex-1">{{ $doc->name }}</span>
        <div class="flex items-center gap-1">
            @if($doc->trashed())
                <flux:button
                    wire:click="restoreDocument({{ $doc->id }})"
                    variant="ghost"
                    size="sm"
                    icon="arrow-path"
                    class="text-white hover:bg-green-500/50"
                    title="Restaurar"
                />
                <flux:button
                    wire:click="deleteDocument({{ $doc->id }})"
                    wire:confirm="Tem certeza que deseja excluir permanentemente este arquivo?"
                    variant="ghost"
                    size="sm"
                    icon="trash"
                    class="text-white hover:bg-red-500/50"
                    title="Excluir Permanentemente"
                />
            @else
                <flux:button
                    wire:click="toggleFavorite({{ $doc->id }})"
                    variant="ghost"
                    size="sm"
                    icon="star"
                    class="text-white {{ $doc->is_favorite ? 'text-yellow-400 hover:bg-yellow-400/20' : 'hover:bg-white/20' }}"
                    title="Favoritar"
                />
                <a href="{{ asset('storage/' . $doc->path) }}" target="_blank">
                    <flux:button variant="ghost" size="sm" icon="eye" class="text-white hover:bg-white/20" title="Visualizar" />
                </a>
                <flux:button
                    wire:click="deleteDocument({{ $doc->id }})"
                    wire:confirm="Mover para a lixeira?"
                    wire:loading.attr="disabled"
                    variant="ghost"
                    size="sm"
                    icon="trash"
                    class="text-white hover:bg-red-500/50"
                    title="Mover para Lixeira"
                />
            @endif
        </div>
    </div>
</div>
