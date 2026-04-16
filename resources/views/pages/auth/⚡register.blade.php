<?php // resources/views/pages/auth/⚡register.blade.php

use Livewire\Component;
use App\Models\User;
use App\Models\Plan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public $name = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';

    public function register()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|confirmed|min:8',
        ]);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'plan_id' => Plan::where('slug', 'free')->first()?->id,
        ]);

        Auth::login($user);

        return redirect('/dashboard');
    }
};
?>

<div class="max-w-md mx-auto py-24 px-6 min-h-screen flex flex-col justify-center">
    <div class="text-center mb-10">
        <h1 class="text-4xl font-black tracking-tight text-zinc-900 dark:text-white mb-2">Criar conta</h1>
        <p class="text-zinc-500 dark:text-zinc-400 font-medium tracking-tight">Junte-se à comunidade PixelVault</p>
    </div>

    <!-- Glassmorphism Container -->
    <div class="p-8 bg-white/50 dark:bg-zinc-900/50 backdrop-blur-xl border border-zinc-200 dark:border-zinc-800 rounded-3xl shadow-2xl shadow-indigo-500/5">
        <form wire:submit="register" class="space-y-6">
            <flux:input wire:model="name" label="Nome Completo" placeholder="Seu nome" icon="user" class="dark:bg-zinc-900/50" />
            
            <flux:input wire:model="email" label="E-mail" type="email" placeholder="seu@email.com" icon="envelope" class="dark:bg-zinc-900/50" />
            
            <flux:input wire:model="password" label="Senha" type="password" placeholder="Mínimo 8 caracteres" icon="lock-closed" class="dark:bg-zinc-900/50" />
            
            <flux:input wire:model="password_confirmation" label="Confirmar Senha" type="password" placeholder="Repita a senha" icon="shield-check" class="dark:bg-zinc-900/50" />

            <flux:button type="submit" variant="primary" class="w-full bg-indigo-600 hover:bg-indigo-700 h-12 shadow-lg shadow-indigo-500/20">
                Criar minha conta
            </flux:button>
        </form>

        <div class="mt-8 text-center text-sm">
            <span class="text-zinc-500 dark:text-zinc-400 font-medium">Já tem conta?</span>
            <flux:link href="/login" class="font-black text-indigo-600 hover:text-indigo-500 ml-1">Fazer login</flux:link>
        </div>
    </div>
</div>