<?php

use Livewire\Component;
use App\Models\Comanda;
use App\Models\ComandaDetalle;
use App\Models\Mesa;

new class extends Component
{
    public bool $showConfirm = false;
    public ?int $comandaAFinalizar = null;

    public function with(): array
    {
        $comandas = Comanda::query()
            ->with([
                'mesa',
                'mesero',
                'detalles.platillo',
                'detalles.tamano',
            ])
            ->whereNotIn('estado', ['finalizado', 'cerrado'])
            ->orderByDesc('created_at')
            ->get();

        return compact('comandas');
    }

    public function pedirConfirmacion(int $comandaId): void
    {
        $this->comandaAFinalizar = $comandaId;
        $this->showConfirm = true;
    }

    public function confirmarFinalizar(): void
    {
        if (!$this->comandaAFinalizar) return;

        $comanda = Comanda::findOrFail($this->comandaAFinalizar);
        $comanda->update(['estado' => 'finalizado']);
        ComandaDetalle::where('comanda_id', $this->comandaAFinalizar)->update(['estado' => 'listo']);
        Mesa::whereKey($comanda->mesa_id)->update(['estado' => 'libre']);

        $this->showConfirm = false;
        $this->comandaAFinalizar = null;
    }

    public function cancelarConfirmacion(): void
    {
        $this->showConfirm = false;
        $this->comandaAFinalizar = null;
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
        <div class="max-w-screen-2xl mx-auto px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="text-3xl" style="filter: drop-shadow(0 0 8px #6366f1);">📋</div>
                <div>
                    <div class="text-xs tracking-widest uppercase" style="color: #6366f1; letter-spacing: .2em;">Vista General</div>
                    <div class="font-black text-2xl tracking-tight" style="color: #f1f5f9;">MONITOR GENERAL</div>
                </div>
            </div>

            <a href="{{ route('pin') }}"
               class="px-4 py-2 rounded-xl text-sm font-bold transition-all text-slate-400 hover:text-white hover:bg-slate-800">
                📲 Volver a PIN
            </a>

            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-2 text-xs px-3 py-1.5 rounded-full"
                      style="background: #1e1b4b; border: 1px solid #6366f144; color: #6366f1;">
                    <span class="w-2 h-2 rounded-full animate-pulse inline-block" style="background: #6366f1;"></span>
                    EN VIVO · actualiza c/5s
                </span>
                <div class="text-sm" style="color: #475569;">{{ now()->format('H:i') }}</div>
            </div>
        </div>
    </header>

    <main class="max-w-screen-2xl mx-auto px-4 py-6">

        {{-- Contador --}}
        <div class="flex items-center gap-2 mb-5 px-1">
            <span class="text-base">📋</span>
            <span class="font-black text-sm tracking-widest uppercase" style="color: #6366f1;">Todas las órdenes activas</span>
            <span class="ml-2 text-xs font-black px-2 py-0.5 rounded-full"
                  style="background: #6366f122; color: #6366f1; border: 1px solid #6366f144;">
                {{ $comandas->count() }}
            </span>
        </div>

        @if($comandas->isEmpty())
            <div class="flex flex-col items-center justify-center py-32 gap-3" style="color: #334155;">
                <div class="text-6xl opacity-20">✅</div>
                <div class="text-xl font-bold">Sin órdenes activas</div>
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
                         style="background: #6366f111; border-bottom: 1px solid #1e2530;">
                        <div class="flex items-center gap-2">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center font-black text-base"
                                 style="background: #6366f122; color: #6366f1; border: 1.5px solid #6366f155;">
                                {{ $comanda->mesa?->numero ?? '?' }}
                            </div>
                            <div>
                                <div class="text-xs" style="color: #475569;">Mesa</div>
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
                            @php
                                $areaId = $detalle->platillo?->area_id;
                                $areaColor = match($areaId) {
                                    1 => ['bg' => '#1f0a0a', 'border' => '#ef444433', 'badge' => '#ef444422', 'text' => '#ef4444'],
                                    2 => ['bg' => '#0f1a0a', 'border' => '#22c55e33', 'badge' => '#22c55e22', 'text' => '#22c55e'],
                                    3 => ['bg' => '#0a0f1f', 'border' => '#3b82f633', 'badge' => '#3b82f622', 'text' => '#3b82f6'],
                                    default => ['bg' => '#0d1117', 'border' => '#1e2530', 'badge' => '#1e253088', 'text' => '#94a3b8'],
                                };
                            @endphp
                            <div class="rounded-xl p-3 flex items-start gap-3"
                                 style="background: {{ $areaColor['bg'] }}; border: 1px solid {{ $areaColor['border'] }};">
                                <div class="w-7 h-7 rounded-lg flex items-center justify-center font-black text-xs flex-shrink-0"
                                     style="background: {{ $areaColor['badge'] }}; color: {{ $areaColor['text'] }};">
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

                    {{-- Footer --}}
                    <div class="px-4 py-3 flex items-center justify-between gap-2"
                         style="border-top: 1px solid #1e2530;">
                        <div class="text-sm font-black" style="color: #94a3b8;">
                            L <span style="color: #f1f5f9;">{{ number_format($comanda->total, 2) }}</span>
                        </div>
                        <button
                            type="button"
                            wire:click="pedirConfirmacion({{ $comanda->id }})"
                            class="text-xs px-3 py-1.5 rounded-xl font-bold hover:opacity-80 transition-all"
                            style="background: #22c55e22; border: 1px solid #22c55e44; color: #22c55e;"
                        >
                            ✓ Finalizar
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        @endif
    </main>

    {{-- MODAL DE CONFIRMACIÓN --}}
    @if($showConfirm)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">

        {{-- Fondo --}}
        <div class="absolute inset-0" style="background: rgba(0,0,0,0.75);"
             wire:click="cancelarConfirmacion"></div>

        {{-- Modal --}}
        <div class="relative w-full max-w-sm rounded-2xl p-6 flex flex-col gap-5"
             style="background: #0d1117; border: 1.5px solid #22c55e44; box-shadow: 0 0 60px #22c55e11, 0 24px 48px rgba(0,0,0,0.5);">

            {{-- Ícono --}}
            <div class="flex items-center justify-center">
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-3xl"
                     style="background: #22c55e22; border: 1.5px solid #22c55e44;">
                    ✓
                </div>
            </div>

            {{-- Texto --}}
            <div class="text-center">
                <div class="font-black text-lg" style="color: #f1f5f9;">¿Finalizar comanda?</div>
                <div class="text-sm mt-2 leading-relaxed" style="color: #475569;">
                    La mesa quedará marcada como
                    <span style="color: #22c55e; font-weight: 700;">libre</span>
                    y la comanda pasará a finalizadas.
                </div>
            </div>

            {{-- Botones --}}
            <div class="flex gap-3">
                <button
                    type="button"
                    wire:click="cancelarConfirmacion"
                    class="flex-1 py-3 rounded-xl font-bold text-sm transition-all hover:opacity-80"
                    style="background: #1e2530; color: #94a3b8; border: 1.5px solid #2a3441;"
                >
                    Cancelar
                </button>
                <button
                    type="button"
                    wire:click="confirmarFinalizar"
                    class="flex-1 py-3 rounded-xl font-bold text-sm transition-all hover:opacity-80"
                    style="background: #22c55e22; color: #22c55e; border: 1.5px solid #22c55e44;"
                >
                    ✓ Sí, finalizar
                </button>
            </div>
        </div>
    </div>
    @endif

</div>
