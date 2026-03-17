<?php

use Livewire\Component;
use App\Models\Comanda;
use App\Models\ComandaDetalle;

new class extends Component
{
   public function with(): array
{
    $comandas = Comanda::query()
        ->with([
            'mesa',
            'mesero',
            'detalles' => fn($q) => $q->whereHas('platillo', fn($q) => $q->where('area_id', 3)),
            'detalles.platillo',
            'detalles.tamano',
        ])
 ->whereNotIn('estado', ['finalizado', 'cerrado'])
        ->whereHas('detalles.platillo', fn($q) => $q->where('area_id', 3))
        ->orderByDesc('created_at')
        ->get();

    // Excluir comandas que quedaron sin detalles tras el filtro
    $comandas = $comandas->filter(fn($c) => $c->detalles->isNotEmpty());

    return compact('comandas');
}
   
};
?>

<div
    class="min-h-screen text-white"
    style="background: #080a0e; font-family: 'Courier New', monospace;"
    wire:poll.5s
>

    {{-- HEADER --}}
    <header style="background: #0d1117; border-bottom: 1px solid #1e2530;">
             @include('partials.navbar')
        <div class="max-w-screen-2xl mx-auto px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="text-3xl" style="filter: drop-shadow(0 0 8px #f97316);">🔥</div>
                <div>
                    <div class="text-xs tracking-widest uppercase" style="color: #f97316; letter-spacing: .2em;">Sistema de Cocina</div>
                    <div class="font-black text-2xl tracking-tight" style="color: #f1f5f9;">MONITOR DE COMANDAS - BEBIDAS</div>
                </div>
            </div>

          

            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-2 text-xs px-3 py-1.5 rounded-full"
                      style="background: #14290f; border: 1px solid #22c55e44; color: #22c55e;">
                    <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse inline-block"></span>
                    EN VIVO · actualiza c/5s
                </span>
                <div class="text-sm" style="color: #475569;">{{ now()->format('H:i') }}</div>
            </div>
        </div>
    </header>

    <main class="max-w-screen-2xl mx-auto px-4 py-6">

        {{-- Contador --}}
        <div class="flex items-center gap-2 mb-5 px-1">
            <span class="text-base">🆕</span>
            <span class="font-black text-sm tracking-widest uppercase" style="color: #ef4444;">Pedidos activos</span>
            <span class="ml-2 text-xs font-black px-2 py-0.5 rounded-full"
                  style="background: #ef444422; color: #ef4444; border: 1px solid #ef444444;">
                {{ $comandas->count() }}
            </span>
        </div>

        @if($comandas->isEmpty())
            <div class="flex flex-col items-center justify-center py-32 gap-3" style="color: #334155;">
                <div class="text-6xl opacity-20">✅</div>
                <div class="text-xl font-bold">Sin pedidos activos</div>
                <div class="text-sm">Las nuevas órdenes aparecerán aquí automáticamente</div>
            </div>
        @else

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-5">
            @foreach($comandas as $comanda)
                @php $urgente = $comanda->created_at->diffInMinutes(now()) > 15; @endphp

                <div class="rounded-2xl overflow-hidden flex flex-col"
                     style="background: #0d1117; border: 1.5px solid {{ $urgente ? '#ef444466' : '#1e2530' }}; {{ $urgente ? 'box-shadow: 0 0 0 2px #ef444433;' : '' }}">

                    {{-- Card header --}}
                    <div class="px-4 py-3 flex items-center justify-between"
                         style="background: #ef444411; border-bottom: 1px solid #1e2530;">
                        <div class="flex items-center gap-2">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center font-black text-base"
                                 style="background: #ef444422; color: #ef4444; border: 1.5px solid #ef444455;">
                                {{ $comanda->mesa?->numero_zona ?? '?' }}
                            </div>
                            <div>
                                <div class="text-xs" style="color: #475569;">{{ $comanda->mesa?->zona ?? 'Zona' }}</div>
                                <div class="text-sm font-bold" style="color: #e2e8f0;">{{ $comanda->mesero?->nombre ?? '—' }}</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs font-mono" style="color: {{ $urgente ? '#ef4444' : '#475569' }};">
                                {{ $urgente ? '⚠ ' : '' }}{{ $comanda->created_at->diffForHumans(['short' => true]) }}
                            </div>
                            <div class="text-xs font-mono" style="color: #334155;">#{{ str_pad($comanda->id, 4, '0', STR_PAD_LEFT) }}</div>
                        </div>
                    </div>

                    {{-- Ítems --}}
                    <div class="flex-1 px-3 py-2 space-y-2">
                        @foreach($comanda->detalles as $detalle)
                            <div class="rounded-xl p-3 flex items-start gap-3"
                                 style="background: #1f0a0a; border: 1px solid #ef444433;">
                                <div class="w-7 h-7 rounded-lg flex items-center justify-center font-black text-xs flex-shrink-0"
                                     style="background: #ef444422; color: #ef4444;">
                                    {{ $detalle->cantidad }}×
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-bold text-sm truncate" style="color: #e2e8f0;">
                                        {{ $detalle->platillo?->nombre ?? 'Platillo' }}
                                    </div>
                                    @if($detalle->tamano)
                                        <div class="text-xs" style="color: #64748b;">{{ $detalle->tamano->nombre }}</div>
                                    @endif
                                    @if($detalle->notas)
                                        <div class="text-xs italic mt-0.5 px-1.5 py-0.5 rounded"
                                             style="background: #1e2530; color: #94a3b8;">📝 {{ $detalle->notas }}</div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Footer con total + botón finalizar --}}
                    <div class="px-4 py-3 flex items-center justify-between gap-2"
                         style="border-top: 1px solid #1e2530;">
                        <div class="text-sm font-black" style="color: #94a3b8;">
                            L <span style="color: #f1f5f9;">{{ number_format($comanda->total, 2) }}</span>
                        </div>
                        
                    </div>
                </div>
            @endforeach
        </div>

        @endif
    </main>
</div>
