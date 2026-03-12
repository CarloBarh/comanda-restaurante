<?php

use Livewire\Component;
use App\Models\Comanda;

new class extends Component
{
    public string $fecha = '';

    public function mount(): void
    {
        $this->fecha = now()->toDateString();
    }

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
            ->whereDate('created_at', $this->fecha)
            ->orderByDesc('created_at')
            ->get();

        $totalDelDia = $comandas->sum('total');
        $totalItems  = $comandas->flatMap->detalles->sum('cantidad');

        return compact('comandas', 'totalDelDia', 'totalItems');
    }

    public function imprimirYCerrar(): void
    {
        // Marcar todas las comandas finalizadas del día como cerradas
        Comanda::query()
            ->where('estado', 'finalizado')
            ->whereDate('created_at', $this->fecha)
            ->update(['estado' => 'cerrado']);

        // Disparar el print desde JS
        $this->dispatch('imprimir');
    }
};
?>

<div
    class="min-h-screen text-white"
    style="background: #080a0e; font-family: 'Courier New', monospace;"
    x-data
    @imprimir.window="window.print()"
>

    {{-- HEADER (oculto al imprimir) --}}
    <header class="no-print" style="background: #0d1117; border-bottom: 1px solid #1e2530;">
        <div class="max-w-screen-2xl mx-auto px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="text-3xl">✅</div>
                <div>
                    <div class="text-xs tracking-widest uppercase" style="color: #22c55e; letter-spacing: .2em;">Historial</div>
                    <div class="font-black text-2xl tracking-tight" style="color: #f1f5f9;">COMANDAS FINALIZADAS</div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <label class="text-xs font-bold" style="color: #475569;">Fecha:</label>
                <input
                    type="date"
                    wire:model.live="fecha"
                    class="rounded-xl px-3 py-2 text-sm font-bold outline-none"
                    style="background: #0d1117; border: 1.5px solid #1e2530; color: #e2e8f0;"
                />
            </div>

            <div class="flex items-center gap-3">
                {{-- Botón imprimir --}}
                @if($comandas->isNotEmpty())
                    <button
                        type="button"
                        wire:click="imprimirYCerrar"
                        wire:confirm="¿Imprimir y marcar todas las comandas de hoy como cerradas?"
                        class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold transition-all hover:opacity-80"
                        style="background: #22c55e22; border: 1.5px solid #22c55e44; color: #22c55e;"
                    >
                        🖨️ Imprimir y cerrar
                    </button>
                @endif

                <a href="{{ route('pin') }}"
                   class="px-4 py-2 rounded-xl text-sm font-bold transition-all text-slate-400 hover:text-white hover:bg-slate-800">
                    📲 Volver a PIN
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-screen-2xl mx-auto px-4 py-6">

        {{-- Encabezado solo visible al imprimir --}}
        <div class="print-only mb-6" style="display: none;">
            <div style="text-align: center; border-bottom: 2px solid #000; padding-bottom: 12px; margin-bottom: 16px;">
                <div style="font-size: 20px; font-weight: 900; letter-spacing: 2px;">RESUMEN DE COMANDAS</div>
                <div style="font-size: 13px; margin-top: 4px;">Fecha: {{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}</div>
                <div style="font-size: 12px; color: #555;">Impreso: {{ now()->format('d/m/Y H:i') }}</div>
            </div>
        </div>

        {{-- Resumen del día --}}
        <div class="no-print grid grid-cols-3 gap-4 mb-6">
            <div class="rounded-xl px-5 py-4 flex items-center gap-4"
                 style="background: #071510; border: 1px solid #22c55e33;">
                <div class="text-4xl font-black" style="color: #22c55e;">{{ $comandas->count() }}</div>
                <div class="text-sm font-semibold" style="color: #22c55e99;">comandas<br>finalizadas</div>
            </div>
            <div class="rounded-xl px-5 py-4 flex items-center gap-4"
                 style="background: #071510; border: 1px solid #22c55e33;">
                <div class="text-4xl font-black" style="color: #22c55e;">{{ $totalItems }}</div>
                <div class="text-sm font-semibold" style="color: #22c55e99;">ítems<br>vendidos</div>
            </div>
            <div class="rounded-xl px-5 py-4 flex items-center gap-4"
                 style="background: #071510; border: 1px solid #22c55e33;">
                <div class="text-2xl font-black" style="color: #22c55e;">L {{ number_format($totalDelDia, 2) }}</div>
                <div class="text-sm font-semibold" style="color: #22c55e99;">total<br>del día</div>
            </div>
        </div>

        @if($comandas->isEmpty())
            <div class="no-print flex flex-col items-center justify-center py-32 gap-3" style="color: #334155;">
                <div class="text-6xl opacity-20">📋</div>
                <div class="text-xl font-bold">Sin comandas finalizadas</div>
                <div class="text-sm">No hay registros para la fecha seleccionada</div>
            </div>
        @else

        {{-- Tabla unificada --}}
        <div class="rounded-2xl overflow-hidden" style="border: 1.5px solid #1e2530;">

            {{-- Encabezado tabla --}}
            <div class="grid px-4 py-2 text-xs font-black tracking-widest uppercase"
                 style="background: #0d1117; color: #334155; border-bottom: 1px solid #1e2530;
                        grid-template-columns: 60px 60px 1fr 120px 80px 80px 100px;">
                <div>#</div>
                <div>Mesa</div>
                <div>Platillo</div>
                <div>Mesero</div>
                <div class="text-center">Cant.</div>
                <div class="text-right">Precio</div>
                <div class="text-right">Hora</div>
            </div>

            @foreach($comandas as $comanda)
                @foreach($comanda->detalles as $i => $detalle)
                    <div
                        class="grid px-4 py-3 items-center transition-all hover:bg-white/[0.02]"
                        style="grid-template-columns: 60px 60px 1fr 120px 80px 80px 100px;
                               border-bottom: 1px solid #1e2530;
                               background: {{ $loop->parent->even ? '#0a0d12' : '#0d1117' }};"
                    >
                        <div class="text-xs font-mono" style="color: #334155;">
                            @if($i === 0) #{{ str_pad($comanda->id, 4, '0', STR_PAD_LEFT) }} @endif
                        </div>

                        <div>
                            @if($i === 0)
                                <span class="text-sm font-black px-2 py-0.5 rounded-lg"
                                      style="background: #22c55e22; color: #22c55e;">
                                    {{ $comanda->mesa?->numero ?? '?' }}
                                </span>
                            @endif
                        </div>

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

                        <div class="text-sm" style="color: #64748b;">
                            @if($i === 0) {{ $comanda->mesero?->nombre ?? '—' }} @endif
                        </div>

                        <div class="text-center font-black text-sm" style="color: #94a3b8;">
                            {{ $detalle->cantidad }}
                        </div>

                        <div class="text-right font-black text-sm" style="color: #f1f5f9;">
                            L {{ number_format($detalle->subtotal, 2) }}
                        </div>

                        <div class="text-right text-xs font-mono" style="color: #334155;">
                            @if($i === 0) {{ $comanda->created_at->format('H:i') }} @endif
                        </div>
                    </div>
                @endforeach

                {{-- Subtotal por comanda --}}
                <div class="grid px-4 py-2 items-center"
                     style="grid-template-columns: 60px 60px 1fr 120px 80px 80px 100px;
                            background: #071510; border-bottom: 2px solid #1e2530;">
                    <div></div>
                    <div></div>
                    <div class="text-xs font-bold" style="color: #22c55e66;">
                        {{ $comanda->detalles->count() }} ítem(s)
                    </div>
                    <div></div>
                    <div></div>
                    <div class="text-right font-black text-sm" style="color: #22c55e;">
                        L {{ number_format($comanda->total, 2) }}
                    </div>
                    <div></div>
                </div>
            @endforeach

            {{-- Total general --}}
            <div class="grid px-4 py-4 items-center"
                 style="grid-template-columns: 60px 60px 1fr 120px 80px 80px 100px;
                        background: #0d1117;">
                <div></div>
                <div></div>
                <div class="text-xs font-black tracking-widest uppercase" style="color: #22c55e;">
                    Total del día
                </div>
                <div></div>
                <div></div>
                <div class="text-right font-black text-lg" style="color: #22c55e;">
                    L {{ number_format($totalDelDia, 2) }}
                </div>
                <div></div>
            </div>
        </div>

        @endif
    </main>
</div>

{{-- Estilos de impresión --}}
<style>
    @media print {
        .no-print { display: none !important; }
        .print-only { display: block !important; }

        body {
            background: #fff !important;
            color: #000 !important;
            font-family: monospace;
        }

        [style*="background"] {
            background: #fff !important;
            color: #000 !important;
        }

        header, nav { display: none !important; }

        main {
            padding: 0 !important;
            max-width: 100% !important;
        }

        .rounded-2xl {
            border-radius: 0 !important;
            border: 1px solid #ccc !important;
        }

        /* Filas de tabla en blanco y negro */
        [class*="grid"] {
            border-bottom: 1px solid #ddd !important;
        }

        /* Ocultar resumen de colores */
        .grid.grid-cols-3 { display: none !important; }
    }
</style>
