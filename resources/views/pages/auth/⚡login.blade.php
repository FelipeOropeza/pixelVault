<?php // resources/views/pages/auth/⚡login.blade.php

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public $email = '';
    public $password = '';

    public function login()
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password])) {
            session()->regenerate();

            return redirect()->intended('/dashboard');
        }

        session()->flash('error', 'As credenciais fornecidas estão incorretas.');
    }
};
?>

<div class="max-w-md mx-auto py-24 px-6 min-h-screen flex flex-col justify-center">
    <div class="text-center mb-10">
        <h1 class="text-4xl font-black tracking-tight text-zinc-900 dark:text-white mb-2">Acesse seu Cofre</h1>
        <p class="text-zinc-500 dark:text-zinc-400 font-medium tracking-tight">Insira suas credenciais para continuar no PixelVault</p>
    </div>

    <!-- Glassmorphism Container -->
    <div class="p-8 bg-white/50 dark:bg-zinc-900/50 backdrop-blur-xl border border-zinc-200 dark:border-zinc-800 rounded-3xl shadow-2xl shadow-indigo-500/5">
        @if (session()->has('error'))
            <div class="mb-6 p-4 bg-red-500/10 border border-red-500/20 text-red-600 dark:text-red-400 rounded-2xl text-sm font-bold flex items-center gap-2">
                <flux:icon icon="exclamation-circle" variant="mini" />
                {{ session('error') }}
            </div>
        @endif

        <form wire:submit="login" class="space-y-6">
            <flux:input wire:model="email" label="E-mail" type="email" placeholder="seu@email.com" icon="envelope" class="dark:bg-zinc-900/50" />
            
            <flux:input wire:model="password" label="Senha" type="password" placeholder="••••••••" icon="lock-closed" class="dark:bg-zinc-900/50" />

            <flux:button type="submit" variant="primary" class="w-full bg-indigo-600 hover:bg-indigo-700 h-12 shadow-lg shadow-indigo-500/20">
                Entrar no PixelVault
            </flux:button>
        </form>

        <div class="mt-8 text-center text-sm">
            <span class="text-zinc-500 dark:text-zinc-400 font-medium">Novo por aqui?</span>
            <flux:link href="/register" class="font-black text-indigo-600 hover:text-indigo-500 ml-1">Criar conta gratuita</flux:link>
        </div>
    </div>
</div>