<?php

use Livewire\Component;

new class extends Component
{
    public $title = '';
    public $content = '';
    public function save()
    {
        $this->validate([
            'title' => 'required|min:3',
            'content' => 'required|min:10',

        ], [
            'title.required' => 'O título é obrigatório',
            'title.min' => 'O título deve ter pelo menos 3 caracteres',
            'content.required' => 'O conteúdo é obrigatório',
            'content.min' => 'O conteúdo deve ter pelo menos 10 caracteres',
        ]);
        session()->flash('success', 'Post created successfully!');
        $this->reset(['title', 'content']); // Limpa os campos após o sucesso
    }
};
?>

<div>
    <span>{{ session('success') }}</span>
    <h1>Create Post</h1>

    <form wire:submit="save">
        <input type="text" wire:model="title" placeholder="Title">
        @error('title') <span class="error">{{ $message }}</span> @enderror
        <textarea wire:model="content" placeholder="Content"></textarea>
        @error('content') <span class="error">{{ $message }}</span> @enderror
        <button type="submit">Create</button>
    </form>
</div>