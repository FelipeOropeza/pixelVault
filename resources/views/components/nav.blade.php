<nav class="fixed top-0 left-0 right-0 z-50 bg-white/80 dark:bg-zinc-900/80 backdrop-blur-xl border-b border-zinc-200 dark:border-zinc-800">
    <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
        <a href="/" class="flex items-center gap-2.5 group">
            <div class="size-9 bg-indigo-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-500/20 group-hover:scale-105 transition-transform">
                <flux:icon icon="photo" variant="mini" class="text-white" />
            </div>
            <span class="font-black text-xl tracking-tighter text-zinc-900 dark:text-white">PixelVault</span>
        </a>

        <div class="flex items-center gap-3">
            @auth
                <flux:button href="/dashboard" variant="ghost" size="sm" icon-leading="squares-2x2">Dashboard</flux:button>
                
                <flux:dropdown>
                    <flux:button variant="ghost" size="sm" suffix-icon="chevron-down">
                        {{ auth()->user()->name }}
                    </flux:button>

                    <flux:menu class="w-48">
                        {{-- Removidos links de Perfil e Configurações --}}
                        <flux:menu.separator />
                        <form method="POST" action="/logout">
                            @csrf
                            <flux:menu.item as="button" type="submit" variant="danger" icon="arrow-right-start-on-rectangle">
                                Sair do Sistema
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            @else
                <flux:button href="/login" variant="ghost" size="sm">Entrar</flux:button>
                <flux:button href="/register" variant="primary" size="sm" class="bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-500/10">Criar Conta</flux:button>
            @endauth
        </div>
    </div>
</nav>
