<?php // resources/views/pages/⚡home.blade.php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div class="pb-24">
    <!-- Hero Section -->
    <section class="pt-24 pb-16 px-6">
        <div class="max-w-4xl mx-auto text-center">
            <h1 class="text-5xl md:text-7xl font-extrabold tracking-tight mb-6">
                Sua galeria de imagens <br>
                <span class="text-indigo-600">simples e segura.</span>
            </h1>

            <p class="text-xl text-zinc-600 mb-10 leading-relaxed max-w-2xl mx-auto">
                O PixelVault é o lugar perfeito para organizar suas memórias visuais de forma minimalista.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <flux:button href="/register" variant="primary" class="w-full sm:w-auto px-8 py-3">
                    Começar agora
                </flux:button>
                <flux:button href="/login" variant="ghost" class="w-full sm:w-auto px-8 py-3">
                    Fazer Login
                </flux:button>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="max-w-6xl mx-auto px-6 mt-12">
        <div class="grid md:grid-cols-3 gap-8">
            <div class="p-8 border border-zinc-200 rounded-2xl">
                <flux:icon icon="folder" class="text-indigo-600 mb-4" />
                <h3 class="text-lg font-bold mb-2">Pastas Inteligentes</h3>
                <p class="text-zinc-500 text-sm">Organize suas fotos por categorias de forma intuitiva.</p>
            </div>

            <div class="p-8 border border-zinc-200 rounded-2xl">
                <flux:icon icon="shield-check" class="text-indigo-600 mb-4" />
                <h3 class="text-lg font-bold mb-2">Segurança Total</h3>
                <p class="text-zinc-500 text-sm">Seus dados protegidos com criptografia de ponta.</p>
            </div>

            <div class="p-8 border border-zinc-200 rounded-2xl">
                <flux:icon icon="bolt" class="text-indigo-600 mb-4" />
                <h3 class="text-lg font-bold mb-2">Acesso Rápido</h3>
                <p class="text-zinc-500 text-sm">Upload e visualização instantânea em qualquer dispositivo.</p>
            </div>
        </div>
    </section>
</div>