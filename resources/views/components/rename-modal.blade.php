@props(['editingName', 'editingType'])

<flux:modal name="edit-name" class="min-w-[400px]">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">
                Renomear {{ $editingType === 'file' ? 'Arquivo' : 'Pasta' }}
            </flux:heading>
            <flux:subheading>Escolha um novo nome para o item selecionado.</flux:subheading>
        </div>

        <flux:input 
            wire:model="editingName" 
            wire:keydown.enter="saveName" 
            placeholder="Novo nome..." 
            autofocus 
        />

        <div class="flex gap-2">
            <flux:spacer />
            <flux:button variant="ghost" x-on:click="$modal.close()">Cancelar</flux:button>
            <flux:button variant="primary" wire:click="saveName" class="bg-indigo-600 shadow-lg shadow-indigo-500/20">
                Salvar Alterações
            </flux:button>
        </div>
    </div>
</flux:modal>
