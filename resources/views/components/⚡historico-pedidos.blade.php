<?php

use Livewire\Component;
use App\Models\Comanda;

new class extends Component
{
    public string $busqueda = '';
    public string $fechaInicio = '';
    public string $fechaFin = '';

    public function with(): array
    {
        $comandas = Comanda::query()
            ->with([
                'mesa',
                'mesero',
                'detalles.platillo',
                'detalles.tamano',
            ])
            ->where('estado', 'cerrado')
            ->when($this->fechaInicio !== '', fn($q) => $q->whereDate('created_at', '>=', $this->fechaInicio))
            ->when($this->fechaFin !== '', fn($q) => $q->whereDate('created_at', '<=', $this->fechaFin))
            ->when($this->busqueda !== '', fn($q) => $q
                ->whereHas('mesa', fn($q) => $q->where('numero', 'like', '%'.$this->busqueda.'%'))
                ->orWhereHas('mesero', fn($q) => $q->where('nombre', 'like', '%'.$this->busqueda.'%'))
            )
            ->orderByDesc('created_at')
            ->get();

        $totalGeneral = $comandas->sum('total');
        $totalItems   = $comandas->flatMap->detalles->sum('cantidad');

        return compact('comandas', 'totalGeneral', 'totalItems');
    }

    public function limpiarFiltros(): void
    {
        $this->fechaInicio = '';
        $this->fechaFin    = '';
        $this->busqueda    = '';
    }
};
?>

<div
    class="min-h-screen text-white"
    style="background: #080a0e; font-family: 'Courier New', monospace;"
>

    {{-- HEADER --}}
    <header style="background: #0d1117; border-bottom: 1px solid #1e2530;">
             @include('partials.navbar')
        <div class="max-w-screen-2xl mx-auto px-6 py-4 flex items-center justify-between gap-4 flex-wrap">

            <div class="flex items-center gap-4">
                <div class="text-3xl">🗂️</div>
                <div>
                    <div class="text-xs tracking-widest uppercase" style="color: #a855f7; letter-spacing: .2em;">Historial completo</div>
                    <div class="font-black text-2xl tracking-tight" style="color: #f1f5f9;">PEDIDOS CERRADOS</div>
                </div>
            </div>

            {{-- Filtros --}}
            <div class="flex items-center gap-3 flex-wrap">
                <div class="flex items-center gap-2">
                    <label class="text-xs font-bold" style="color: #475569;">Desde:</label>
                    <input
                        type="date"
                        wire:model.live="fechaInicio"
                        class="rounded-xl px-3 py-2 text-sm font-bold outline-none"
                        style="background: #0d1117; border: 1.5px solid #1e2530; color: #e2e8f0;"
                    />
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-xs font-bold" style="color: #475569;">Hasta:</label>
                    <input
                        type="date"
                        wire:model.live="fechaFin"
                        class="rounded-xl px-3 py-2 text-sm font-bold outline-none"
                        style="background: #0d1117; border: 1.5px solid #1e2530; color: #e2e8f0;"
                    />
                </div>
                <input
                    type="text"
                    wire:model.live="busqueda"
                    placeholder="Buscar mesa o mesero..."
                    class="rounded-xl px-3 py-2 text-sm outline-none w-48"
                    style="background: #0d1117; border: 1.5px solid #1e2530; color: #e2e8f0;"
                />
                @if($fechaInicio || $fechaFin || $busqueda)
                    <button
                        wire:click="limpiarFiltros"
                        class="px-3 py-2 rounded-xl text-xs font-bold transition-all hover:opacity-80"
                        style="background: #ef444422; border: 1px solid #ef444444; color: #ef4444;"
                    >
                        ✕ Limpiar
                    </button>
                @endif
            </div>

            <a href="{{ route('pin') }}"
               class="px-4 py-2 rounded-xl text-sm font-bold transition-all text-slate-400 hover:text-white hover:bg-slate-800">
                📲 Volver a PIN
            </a>
        </div>
    </header>

    <main class="max-w-screen-2xl mx-auto px-4 py-6">

        {{-- Estadísticas --}}
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="rounded-xl px-5 py-4 flex items-center gap-4"
                 style="background: #120820; border: 1px solid #a855f733;">
                <div class="text-4xl font-black" style="color: #a855f7;">{{ $comandas->count() }}</div>
                <div class="text-sm font-semibold" style="color: #a855f799;">comandas<br>cerradas</div>
            </div>
            <div class="rounded-xl px-5 py-4 flex items-center gap-4"
                 style="background: #120820; border: 1px solid #a855f733;">
                <div class="text-4xl font-black" style="color: #a855f7;">{{ $totalItems }}</div>
                <div class="text-sm font-semibold" style="color: #a855f799;">ítems<br>vendidos</div>
            </div>
            <div class="rounded-xl px-5 py-4 flex items-center gap-4"
                 style="background: #120820; border: 1px solid #a855f733;">
                <div class="text-2xl font-black" style="color: #a855f7;">L {{ number_format($totalGeneral, 2) }}</div>
                <div class="text-sm font-semibold" style="color: #a855f799;">total<br>recaudado</div>
            </div>
        </div>

        @if($comandas->isEmpty())
            <div class="flex flex-col items-center justify-center py-32 gap-3" style="color: #334155;">
                <div class="text-6xl opacity-20">🗂️</div>
                <div class="text-xl font-bold">Sin registros</div>
                <div class="text-sm">No hay pedidos cerrados para los filtros seleccionados</div>
            </div>
        @else

        {{-- Tabla --}}
        <div class="rounded-2xl overflow-hidden" style="border: 1.5px solid #1e2530;">

            {{-- Encabezado --}}
            <div class="grid px-4 py-2 text-xs font-black tracking-widest uppercase"
                 style="background: #0d1117; color: #334155; border-bottom: 1px solid #1e2530;
                        grid-template-columns: 70px 60px 1fr 130px 80px 80px 110px;">
                <div>#</div>
                <div>Mesa</div>
                <div>Platillo</div>
                <div>Mesero</div>
                <div class="text-center">Cant.</div>
                <div class="text-right">Subtotal</div>
                <div class="text-right">Fecha/Hora</div>
            </div>

            @foreach($comandas as $comanda)
                @foreach($comanda->detalles as $i => $detalle)
                    @php
                        $areaId = $detalle->platillo?->area_id;
                        $areaColor = match($areaId) {
                            1 => '#ef4444',
                            2 => '#22c55e',
                            3 => '#3b82f6',
                            default => '#64748b',
                        };
                    @endphp
                    <div
                        class="grid px-4 py-3 items-center hover:bg-white/[0.015] transition-all"
                        style="grid-template-columns: 70px 60px 1fr 130px 80px 80px 110px;
                               border-bottom: 1px solid #1e2530;
                               background: {{ $loop->parent->even ? '#0a0d12' : '#0d1117' }};"
                    >
                        {{-- ID --}}
                        <div class="text-xs font-mono" style="color: #334155;">
                            @if($i === 0)#{{ str_pad($comanda->id, 4, '0', STR_PAD_LEFT) }}@endif
                        </div>

                        {{-- Mesa --}}
                        <div>
                            @if($i === 0)
                                <span class="text-sm font-black px-2 py-0.5 rounded-lg"
                                      style="background: #a855f722; color: #a855f7;">
                                    {{ $comanda->mesa?->numero ?? '?' }}
                                </span>
                            @endif
                        </div>

                        {{-- Platillo --}}
                        <div class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full flex-shrink-0"
                                  style="background: {{ $areaColor }};"></span>
                            <div>
                                <div class="font-bold text-sm" style="color: #e2e8f0;">
                                    {{ $detalle->platillo?->nombre ?? 'Platillo' }}
                                </div>
                                @if($detalle->tamano)
                                    <div class="text-xs" style="color: #475569;">{{ $detalle->tamano->nombre }}</div>
                                @endif
                                @if($detalle->notas)
                                    <div class="text-xs italic" style="color: #64748b;">📝 {{ $detalle->notas }}</div>
                                @endif
                            </div>
                        </div>

                        {{-- Mesero --}}
                        <div class="text-sm" style="color: #64748b;">
                            @if($i === 0){{ $comanda->mesero?->nombre ?? '—' }}@endif
                        </div>

                        {{-- Cantidad --}}
                        <div class="text-center font-black text-sm" style="color: #94a3b8;">
                            {{ $detalle->cantidad }}
                        </div>

                        {{-- Subtotal --}}
                        <div class="text-right font-black text-sm" style="color: #f1f5f9;">
                            L {{ number_format($detalle->subtotal, 2) }}
                        </div>

                        {{-- Fecha/Hora --}}
                        <div class="text-right text-xs font-mono" style="color: #334155;">
                            @if($i === 0)
                                <div>{{ $comanda->created_at->format('d/m/Y') }}</div>
                                <div>{{ $comanda->created_at->format('H:i') }}</div>
                            @endif
                        </div>
                    </div>
                @endforeach

                {{-- Subtotal por comanda --}}
                <div class="grid px-4 py-2"
                     style="grid-template-columns: 70px 60px 1fr 130px 80px 80px 110px;
                            background: #120820; border-bottom: 2px solid #1e2530;">
                    <div></div>
                    <div></div>
                    <div class="text-xs font-bold" style="color: #a855f766;">
                        {{ $comanda->detalles->count() }} ítem(s)
                    </div>
                    <div></div>
                    <div></div>
                    <div class="text-right font-black text-sm" style="color: #a855f7;">
                        L {{ number_format($comanda->total, 2) }}
                    </div>
                    <div></div>
                </div>
            @endforeach

            {{-- Total general --}}
            <div class="grid px-4 py-4"
                 style="grid-template-columns: 70px 60px 1fr 130px 80px 80px 110px;
                        background: #0d1117;">
                <div></div>
                <div></div>
                <div class="font-black text-xs tracking-widest uppercase" style="color: #a855f7;">
                    Total general
                </div>
                <div></div>
                <div></div>
                <div class="text-right font-black text-lg" style="color: #a855f7;">
                    L {{ number_format($totalGeneral, 2) }}
                </div>
                <div></div>
            </div>
        </div>

        @endif
    </main>
</div>
