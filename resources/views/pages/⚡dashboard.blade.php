<?php // resources/views/pages/⚡dashboard.blade.php

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public function logout()
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect('/');
    }
};
?>

<div class="max-w-7xl mx-auto py-12 px-6">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-black tracking-tight">Meu Cofre</h1>
            <p class="text-zinc-500 text-sm mt-1">Bem-vindo, {{ auth()->user()->name }}!</p>
        </div>

        <flux:button wire:click="logout" variant="ghost" size="sm" icon-leading="arrow-right-start-on-rectangle">
            Sair
        </flux:button>
    </div>

    <!-- Dashboard Content -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- Sidebar/Folders -->
        <div class="md:col-span-1 space-y-4">
            <flux:button variant="primary" class="w-full" icon-leading="plus">Nova Pasta</flux:button>
            
            <nav class="space-y-1">
                <flux:navlist>
                    <flux:navlist.item href="#" icon="home" current>Todas as fotos</flux:navlist.item>
                    <flux:navlist.item href="#" icon="star">Favoritos</flux:navlist.item>
                    <flux:navlist.item href="#" icon="clock">Recentes</flux:navlist.item>
                    <flux:navlist.item href="#" icon="trash">Lixeira</flux:navlist.item>
                </flux:navlist>
            </nav>
        </div>

        <!-- Main Content (Images Grid) -->
        <div class="md:col-span-3">
            <div class="p-12 border-2 border-dashed border-zinc-200 rounded-3xl flex flex-col items-center justify-center text-zinc-400">
                <flux:icon icon="photo" class="size-12 mb-4 opacity-20" />
                <p class="font-bold text-sm tracking-widest uppercase opacity-40">Nenhuma imagem ainda</p>
                <flux:button size="sm" variant="ghost" class="mt-4">Fazer primeiro upload</flux:button>
            </div>
        </div>
    </div>
</div>