<?php // resources/views/pages/⚡plans.blade.php

use Livewire\Component;
use App\Models\Plan;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public function selectPlan($planId)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->update(['plan_id' => $planId]);
        
        $this->js('alert("Plano atualizado com sucesso!")');
        return redirect('/dashboard');
    }

    public function with()
    {
        return [
            'plans' => Plan::all(),
        ];
    }
};
?>

<div class="max-w-6xl mx-auto py-24 px-6 mt-12">
    <div class="text-center mb-16">
        <h1 class="text-5xl font-black tracking-tight text-zinc-900 dark:text-white mb-4">Escolha seu plano</h1>
        <p class="text-xl text-zinc-500 font-medium tracking-tight">Expanda seu cofre digital e guarde mais memórias.</p>
    </div>

    <div class="grid md:grid-cols-3 gap-8">
        @foreach($plans as $plan)
            <div wire:key="plan-{{ $plan->id }}" class="relative group p-8 bg-white dark:bg-zinc-900 rounded-[2.5rem] border border-zinc-200 dark:border-zinc-800 transition-all hover:shadow-2xl hover:shadow-indigo-500/10 hover:-translate-y-2">
                @if(auth()->user()->plan_id === $plan->id)
                    <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-indigo-600 text-white text-[10px] font-black uppercase tracking-widest px-4 py-1 rounded-full shadow-lg">
                        Plano Atual
                    </div>
                @endif

                <div class="mb-8">
                    <h3 class="text-2xl font-black text-zinc-900 dark:text-white mb-2">{{ $plan->name }}</h3>
                    <p class="text-sm text-zinc-500 font-medium leading-relaxed">{{ $plan->description }}</p>
                </div>

                <div class="flex items-baseline gap-1 mb-8">
                    <span class="text-4xl font-black text-zinc-900 dark:text-white">
                        {{ $plan->storage_limit_bytes >= 1073741824 ? round($plan->storage_limit_bytes / 1073741824) . 'GB' : round($plan->storage_limit_bytes / 1048576) . 'MB' }}
                    </span>
                    <span class="text-zinc-400 font-bold uppercase tracking-widest text-xs">de espaço</span>
                </div>

                <div class="space-y-4 mb-10">
                    <div class="flex items-center gap-3">
                        <flux:icon icon="check-circle" class="size-5 text-indigo-500" />
                        <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Suporte a fotos 4K</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <flux:icon icon="check-circle" class="size-5 text-indigo-500" />
                        <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Criptografia de ponta</span>
                    </div>
                    @if($plan->slug !== 'free')
                        <div class="flex items-center gap-3">
                            <flux:icon icon="check-circle" class="size-5 text-indigo-500" />
                            <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Suporte prioritário</span>
                        </div>
                    @endif
                </div>

                <flux:button 
                    wire:click="selectPlan({{ $plan->id }})" 
                    variant="{{ auth()->user()->plan_id === $plan->id ? 'ghost' : 'primary' }}" 
                    class="w-full h-12 {{ auth()->user()->plan_id === $plan->id ? 'opacity-50 cursor-default' : 'bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-500/20' }}"
                    :disabled="auth()->user()->plan_id === $plan->id"
                >
                    {{ auth()->user()->plan_id === $plan->id ? 'Plano Ativo' : 'Selecionar ' . $plan->name }}
                </flux:button>
            </div>
        @endforeach
    </div>

    <div class="mt-20 text-center">
        <flux:button href="/dashboard" variant="ghost" icon-leading="arrow-left">Voltar ao cofre</flux:button>
    </div>
</div>
