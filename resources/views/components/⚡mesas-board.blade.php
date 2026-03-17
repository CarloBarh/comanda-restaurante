<?php

use Livewire\Component;
use App\Models\Mesa;

new class extends Component
{
    public function with(): array
    {
        return [
            'mesas' => Mesa::query()
                ->orderBy('zona')
                ->orderBy('numero_zona')
                ->get(),
        ];
    }

    public function abrirMesa(int $mesaId)
    {
        return redirect()->route('comandas.tomar', ['mesa' => $mesaId]);
    }
};
?>

<div class="min-h-screen bg-[#111315] text-white p-3 md:p-5" wire:poll.visible.5s>
    @include('partials.navbar')
    <div class="max-w-[1200px] mx-auto">

        <div class="flex items-center justify-between gap-4 mb-4">
            <div>
                <h1 class="text-2xl md:text-[28px] font-black tracking-tight text-[#f5f1e8]">
                    Croquis de Mesas
                </h1>
                <p class="text-sm text-[#b8b2a7] mt-1">
                    Selecciona una mesa para tomar o continuar una orden
                </p>
            </div>

            <div class="flex items-center gap-4 text-sm">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full inline-block" style="background:#65c18c;"></span>
                    <span class="text-[#d6d0c4]">Libre</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full inline-block" style="background:#d97706;"></span>
                    <span class="text-[#d6d0c4]">Ocupada</span>
                </div>
            </div>
        </div>

        {{-- Lienzo responsive --}}
        <div
            class="relative w-full rounded-[28px] border shadow-2xl overflow-hidden"
            style="
                aspect-ratio: 4 / 3;
                background: linear-gradient(180deg, #181b1f 0%, #121417 100%);
                border-color:#2a2f36;
            "
        >

            {{-- Área Corredor --}}
            <div class="absolute rounded-[24px] border shadow-lg"
                 style="
                    left: 29.6%;
                    top: 4.4%;
                    width: 35%;
                    height: 44.5%;
                    background: rgba(30,34,39,.70);
                    border-color:#4a3f35;
                 ">
                <div class="absolute text-[#e8dcc6] text-[1.4vw] font-bold tracking-wide"
                     style="left: 30%; top: 3%;">
                    Área Corredor
                </div>
            </div>

            {{-- Salón --}}
            <div class="absolute rounded-[20px] border"
                 style="
                    left: 31.7%;
                    top: 19.4%;
                    width: 21.2%;
                    height: 17.1%;
                    background: rgba(42,47,54,.80);
                    border-color:#5a5045;
                 ">
                <div class="absolute text-[#e8dcc6] text-[1.2vw] font-bold tracking-wide"
                     style="left: 5%; top: 4%;">
                    Salón
                </div>
            </div>

            {{-- Área Verde --}}
            <div class="absolute rounded-[24px] border shadow-lg"
                 style="
                    left: 3.3%;
                    top: 51.7%;
                    width: 34.6%;
                    height: 40.5%;
                    background: rgba(30,34,39,.70);
                    border-color:#4a3f35;
                 ">
                <div class="absolute text-[#e8dcc6] text-[2vw] font-bold tracking-wide"
                     style="left: 31%; top: 41%;">
                    Área Verde
                </div>
            </div>

            {{-- Área de Fuente --}}
            <div class="absolute rounded-[24px] border shadow-lg"
                 style="
                    left: 39.6%;
                    top: 51.7%;
                    width: 25.8%;
                    height: 40.5%;
                    background: rgba(30,34,39,.70);
                    border-color:#4a3f35;
                 ">
                <div class="absolute text-[#e8dcc6] text-[2vw] font-bold tracking-wide"
                     style="left: 10%; top: 5%;">
                    Área de Fuente
                </div>
            </div>

            {{-- Área de Niños --}}
            <div class="absolute rounded-[24px] border shadow-lg"
                 style="
                    left: 67.1%;
                    top: 51.7%;
                    width: 28.7%;
                    height: 40.5%;
                    background: rgba(30,34,39,.70);
                    border-color:#4a3f35;
                 ">
                <div class="absolute text-[#e8dcc6] text-[2vw] font-bold tracking-wide"
                     style="left: 10%; top: 5%;">
                    Área de Niños
                </div>
            </div>

            {{-- Mesas --}}
            @foreach($mesas as $mesa)
                @php
                    $ocupada = $mesa->estado === 'ocupada';
                @endphp

                <button
                    type="button"
                    wire:click="abrirMesa({{ $mesa->id }})"
                    class="
                        absolute rounded-2xl border shadow-lg transition
                        active:scale-[0.97]
                        focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-950
                        overflow-hidden
                        {{ $ocupada
                            ? 'bg-rose-600/20 border-rose-500/40 hover:bg-rose-600/30 focus:ring-rose-500'
                            : 'bg-emerald-600/20 border-emerald-500/40 hover:bg-emerald-600/30 focus:ring-emerald-500'
                        }}
                    "
                    style="
                        left: {{ $mesa->pos_x }}%;
                        top: {{ $mesa->pos_y }}%;
                        width: 5.6%;
                        height: 7.1%;
                    "
                    >
                    {{-- Cuerpo visual de la mesa --}}
                    <div class="absolute inset-[10%]">
                        
                    </div>

                    {{-- Número --}}
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="text-center leading-none">
                            <div class="text-[9px] text-slate-300">Mesa</div>
                            <div class="text-[clamp(14px,1.4vw,22px)] font-black tracking-wide">
                                {{ $mesa->numero_zona ?? $mesa->numero }}
                            </div>
                        </div>
                    </div>

                    {{-- Estado --}}
                    <div class="absolute top-[6%] right-[6%] text-[8px] px-1 py-[2px] rounded-full border
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