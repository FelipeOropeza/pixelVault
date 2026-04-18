<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Document;
use App\Models\Folder;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public string $editingName = '';
    public ?int $editingId = null;
    public string $editingType = ''; // file, folder

    #[On('edit-name')]
    public function load(int $id, string $type): void
    {
        $this->editingId = $id;
        $this->editingType = $type;
        
        if ($type === 'file') {
            $doc = Auth::user()->documents()->findOrFail($id);
            $this->editingName = $doc->name;
        } else {
            $folder = Auth::user()->folders()->findOrFail($id);
            $this->editingName = $folder->name;
        }

        $this->modal('edit-name')->show();
    }

    public function saveName(): void
    {
        $this->validate(['editingName' => 'required|string|max:255']);

        if ($this->editingType === 'file') {
            Auth::user()->documents()->findOrFail($this->editingId)->update(['name' => $this->editingName]);
        } else {
            Auth::user()->folders()->findOrFail($this->editingId)->update(['name' => $this->editingName]);
        }

        $this->modal('edit-name')->close();
        
        // Dispara evento para o dashboard principal se atualizar e mostrar notificação
        $this->dispatch('dashboard-refresh');
        $this->dispatch('notify', 'Nome atualizado com sucesso!');
    }
};
?>

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
