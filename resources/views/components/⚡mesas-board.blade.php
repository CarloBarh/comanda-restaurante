<?php

use Livewire\Component;
use App\Models\Mesa;

new class extends Component
{
    public function with(): array
    {
        return [
            'mesas' => Mesa::query()
                ->orderBy('numero') // simple y seguro (sirve si numero es string)
                ->get(),
        ];
    }
};
?>


<div class="min-h-screen bg-slate-950 text-white p-4 md:p-6">
    <div class="max-w-7xl mx-auto">

        <div class="flex items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold">Croquis de Mesas</h1>
                <p class="text-slate-400 mt-1">Toca una mesa para tomar / ver una comanda</p>
            </div>

            <div class="flex items-center gap-3 text-sm">
                <div class="flex items-center gap-2">
                    <span class="inline-block w-3 h-3 rounded-full bg-emerald-500"></span>
                    <span class="text-slate-300">Libre</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-block w-3 h-3 rounded-full bg-rose-500"></span>
                    <span class="text-slate-300">Ocupada</span>
                </div>
            </div>
        </div>

        <div wire:poll.2s>
            <div class="grid grid-cols-4 sm:grid-cols-6 lg:grid-cols-8 gap-3 md:gap-4">
                @foreach($mesas as $mesa)
                    @php $ocupada = $mesa->estado === 'ocupada'; @endphp

                    <button
                        type="button"
                        class="
                            relative select-none rounded-2xl p-3 md:p-4 border shadow-lg transition
                            active:scale-[0.99]
                            focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-950
                            {{ $ocupada
                                ? 'bg-rose-600/20 border-rose-500/40 hover:bg-rose-600/30 focus:ring-rose-500'
                                : 'bg-emerald-600/20 border-emerald-500/40 hover:bg-emerald-600/30 focus:ring-emerald-500'
                            }}
                        "
                        onclick="window.location='#'"
                    >
                        <div class="
                            w-full h-10 md:h-12 rounded-t-2xl rounded-b-xl border
                            {{ $ocupada ? 'bg-rose-500/25 border-rose-400/30' : 'bg-emerald-500/25 border-emerald-400/30' }}
                        "></div>

                        <div class="
                            mt-2 w-full h-8 md:h-10 rounded-2xl border
                            {{ $ocupada ? 'bg-rose-500/15 border-rose-400/25' : 'bg-emerald-500/15 border-emerald-400/25' }}
                        "></div>

                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="text-center">
                                <div class="text-xs text-slate-300">Mesa</div>
                                <div class="text-xl md:text-2xl font-black tracking-wide">
                                    {{ $mesa->numero }}
                                </div>
                            </div>
                        </div>

                        <div class="absolute top-2 right-2 text-[10px] px-2 py-1 rounded-full border
                            {{ $ocupada
                                ? 'border-rose-400/30 text-rose-200 bg-rose-500/10'
                                : 'border-emerald-400/30 text-emerald-200 bg-emerald-500/10'
                            }}
                        ">
                            {{ $ocupada ? 'OCUPADA' : 'LIBRE' }}
                        </div>
                    </button>
                @endforeach
            </div>
        </div>

    </div>
</div>

