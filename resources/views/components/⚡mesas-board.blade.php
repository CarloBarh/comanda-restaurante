<?php

use Livewire\Component;
use App\Models\Mesa;

new class extends Component
{

    public bool $showPicnicModal = false;
    public function with(): array
    {
        $mesas = Mesa::query()
            ->orderBy('zona')
            ->orderBy('numero_zona')
            ->get();

        $mesasPicnic = $mesas->where('zona', 'Picnic')->values();
        $mesasCroquis = $mesas->where('zona', '!=', 'Picnic')->values();

        return compact('mesasCroquis', 'mesasPicnic');
    }

    public function abrirMesa(int $mesaId)
    {
        return redirect()->route('comandas.tomar', ['mesa' => $mesaId]);
    }

    public function abrirPicnic(): void
    {
        $this->showPicnicModal = true;
    }

    public function cerrarPicnic(): void
    {
        $this->showPicnicModal = false;
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
                     style="left: 40%; top: 41%;">
                    Área Verde
                </div>
                <button
                    type="button"
                    wire:click="abrirPicnic"
                    class="absolute rounded-2xl border shadow-lg transition
                        active:scale-[0.97] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-950
                        bg-amber-600/20 border-amber-500/40 hover:bg-amber-600/30 focus:ring-amber-500"
                    style="
                        left: 45%;
                        top: 56%;
                        width: 28%;
                        height: 15%;
                    "
                    >
                    <div class="absolute inset-[10%]">
                        <div class="w-full h-[42%] rounded-t-2xl rounded-b-xl border bg-amber-500/25 border-amber-400/30"></div>
                        <div class="mt-[8%] w-full h-[32%] rounded-2xl border bg-amber-500/15 border-amber-400/25"></div>
                    </div>

                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="text-center leading-none">
                            <div class="text-[9px] text-slate-300">Zona</div>
                            <div class="text-[clamp(12px,1.2vw,18px)] font-black tracking-wide text-slate-100">
                                Picnic
                            </div>
                        </div>
                    </div>
                </button>
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
            @foreach($mesasCroquis as $mesa)
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
    @if($showPicnicModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/70" wire:click="cerrarPicnic"></div>

            <div class="relative w-full max-w-3xl rounded-3xl border border-slate-700 bg-[#181b1f] shadow-2xl p-6">
                <div class="flex items-center justify-between mb-5">
                    <div>
                        <h2 class="text-2xl font-black text-[#f5f1e8]">Mesas de Picnic</h2>
                        <p class="text-sm text-slate-400 mt-1">Selecciona una mesa para tomar o continuar una orden</p>
                    </div>

                    <button
                        type="button"
                        wire:click="cerrarPicnic"
                        class="w-11 h-11 rounded-xl bg-slate-800 hover:bg-slate-700 text-xl font-black"
                    >
                        ✕
                    </button>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                    @foreach($mesasPicnic as $mesa)
                        @php $ocupada = $mesa->estado === 'ocupada'; @endphp

                        <button
                            type="button"
                            wire:click="abrirMesa({{ $mesa->id }})"
                            class="
                                relative rounded-2xl p-3 border shadow-lg transition
                                active:scale-[0.97]
                                focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-950
                                {{ $ocupada
                                    ? 'bg-rose-600/20 border-rose-500/40 hover:bg-rose-600/30 focus:ring-rose-500'
                                    : 'bg-emerald-600/20 border-emerald-500/40 hover:bg-emerald-600/30 focus:ring-emerald-500'
                                }}
                            "
                        >
                            <div class="
                                w-full h-8 rounded-t-2xl rounded-b-xl border
                                {{ $ocupada ? 'bg-rose-500/25 border-rose-400/30' : 'bg-emerald-500/25 border-emerald-400/30' }}
                            "></div>

                            <div class="
                                mt-2 w-full h-6 rounded-2xl border
                                {{ $ocupada ? 'bg-rose-500/15 border-rose-400/25' : 'bg-emerald-500/15 border-emerald-400/25' }}
                            "></div>

                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="text-center">
                                    <div class="text-xs text-slate-300">Mesa</div>
                                    <div class="text-xl font-black tracking-wide">
                                        {{ $mesa->numero_zona ?? $mesa->numero }}
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
    @endif
</div>