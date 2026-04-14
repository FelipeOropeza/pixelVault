<?php // resources/views/pages/⚡dashboard.blade.php

use Livewire\Component;

new class extends Component {
    // Lógica do Dashboard (como listagem de imagens) entraria aqui futuramente
};
?>

<div class="max-w-7xl mx-auto py-12 px-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-12 animate-fade-in">
        <div class="space-y-1">
            <h1 class="text-4xl font-black tracking-tight text-zinc-900 dark:text-white leading-none">Meu Cofre</h1>
            <p class="text-zinc-500 dark:text-zinc-400 font-medium tracking-tight">Bem-vindo de volta, {{ auth()->user()->name }}!</p>
        </div>

        <div class="flex items-center gap-3">
            <flux:button variant="ghost" icon="magnifying-glass" />
            <flux:button variant="primary" icon-leading="plus" class="bg-indigo-600 hover:bg-indigo-700 h-11 px-6 shadow-lg shadow-indigo-500/20">
                Novo Upload
            </flux:button>
        </div>
    </div>

    <!-- Dashboard Content -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Sidebar Navigation -->
        <aside class="lg:col-span-1 space-y-6">
            <div class="p-6 bg-white/50 dark:bg-zinc-900/50 backdrop-blur-xl rounded-3xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
                <nav class="space-y-1">
                    <flux:navlist>
                        <flux:navlist.item href="#" icon="squares-2x2" current>Todas as Fotos</flux:navlist.item>
                        <flux:navlist.item href="#" icon="star">Favoritos</flux:navlist.item>
                        <flux:navlist.item href="#" icon="clock">Recentes</flux:navlist.item>
                        <flux:navlist.item href="#" icon="trash">Lixeira</flux:navlist.item>
                    </flux:navlist>
                </nav>
            </div>

            <div class="px-2">
                <h3 class="text-xs font-black uppercase tracking-widest text-zinc-400 dark:text-zinc-500 mb-4 px-4 font-black">Minhas Pastas</h3>
                <div class="space-y-1">
                    <flux:button variant="ghost" class="w-full justify-start text-zinc-500" icon-leading="folder">Viagens</flux:button>
                    <flux:button variant="ghost" class="w-full justify-start text-zinc-500" icon-leading="folder">Trabalho</flux:button>
                    <flux:button variant="ghost" class="w-full justify-start text-zinc-400" icon-leading="plus">Nova Pasta</flux:button>
                </div>
            </div>
        </aside>

        <!-- Main Grid Area -->
        <div class="lg:col-span-3">
            <div class="group relative p-20 border-2 border-dashed border-zinc-200 dark:border-zinc-800 rounded-3xl flex flex-col items-center justify-center text-center transition-all hover:border-indigo-300 dark:hover:border-indigo-900 hover:bg-indigo-50/50 dark:hover:bg-indigo-900/5">
                <div class="size-20 bg-zinc-50 dark:bg-zinc-900 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform shadow-sm">
                    <flux:icon icon="photo" class="size-10 text-zinc-300 dark:text-zinc-700" />
                </div>
                
                <h3 class="text-xl font-black text-zinc-900 dark:text-white mb-2 underline decoration-indigo-500/30">Seu cofre está vazio</h3>
                <p class="max-w-xs mx-auto text-sm text-zinc-500 dark:text-zinc-400 font-medium mb-6">Comece enviando suas primeiras imagens para organizar seu portfólio digital.</p>
                
                <flux:button size="sm" variant="ghost" class="text-indigo-600 font-black hover:bg-indigo-50">Fazer primeiro upload</flux:button>
            </div>
        </div>
    </div>
</div>