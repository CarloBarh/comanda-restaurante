<?php

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use App\Models\Caja;
use App\Models\CajaCierre;
use App\Models\CajaActiva;

new class extends Component
{
    public string $fecha = '';

    // ── Modal cierre ────────────────────────────────────────────
    public bool $showCierre = false;

    // ── Modal apertura ──────────────────────────────────────────
    public bool $showApertura = false;
    public string $montoApertura = '';

    public function mount(): void
    {
        $this->fecha = today()->toDateString();
    }

    // ── Estado de la caja ────────────────────────────────────────
    protected function cajaAbierta(): bool
    {
        return CajaActiva::estaAbierta();
    }

    protected function cajaCerradaHoy(): bool
    {
        return CajaCierre::whereDate('fecha', today())->exists();
    }

    // ── Cierre ───────────────────────────────────────────────────
    public function abrirModalCierre(): void
    {
        $this->showCierre = true;
    }

    public function cancelarCierre(): void
    {
        $this->showCierre = false;
    }

    public function confirmarCierre(): void
    {
        $hoy = today()->toDateString();

        $movimientos = Caja::whereDate('created_at', $hoy)
            ->where('estado', 'activo')
            ->get();

        $totalEntradas = $movimientos->where('tipo', 'entrada')->sum('monto');
        $totalSalidas  = $movimientos->where('tipo', 'salida')->sum('monto');
        $balance       = $totalEntradas - $totalSalidas;

        $efectivo      = $movimientos->where('tipo', 'entrada')->where('metodo_pago', 'efectivo')->sum('monto');
        $tarjeta       = $movimientos->where('tipo', 'entrada')->where('metodo_pago', 'tarjeta')->sum('monto');
        $transferencia = $movimientos->where('tipo', 'entrada')->where('metodo_pago', 'transferencia')->sum('monto');

        // Monto de apertura del día
        $apertura = Caja::whereDate('created_at', $hoy)
            ->where('concepto', 'like', 'Apertura de caja%')
            ->where('estado', 'activo')
            ->sum('monto');

        DB::transaction(function () use ($hoy, $totalEntradas, $totalSalidas, $balance, $efectivo, $tarjeta, $transferencia, $apertura) {

            // 1) Guardar cierre
            CajaCierre::create([
                'fecha'          => $hoy,
                'total_entradas' => $totalEntradas,
                'total_salidas'  => $totalSalidas,
                'balance'        => $balance,
                'efectivo'       => $efectivo,
                'tarjeta'        => $tarjeta,
                'transferencia'  => $transferencia,
                'apertura'       => $apertura,
                'cerrada_at'     => now(),
            ]);

            // 2) Marcar movimientos activos de hoy como cerrados
            Caja::whereDate('created_at', $hoy)
                ->where('estado', 'activo')
                ->update(['estado' => 'cerrado']);

            // 3) Marcar caja como inactiva
            CajaActiva::setEstado(false);
        });

        $this->showCierre = false;
        $this->fecha = $hoy; // refrescar vista
    }

    // ── Apertura ─────────────────────────────────────────────────
    public function abrirModalApertura(): void
    {
        $this->montoApertura = '';
        $this->resetErrorBag();
        $this->showApertura = true;
    }

    public function cancelarApertura(): void
    {
        $this->showApertura = false;
        $this->montoApertura = '';
    }

    public function confirmarApertura(): void
    {
        if (trim($this->montoApertura) === '' || !is_numeric($this->montoApertura) || (float) $this->montoApertura < 0) {
            $this->addError('montoApertura', 'Ingresa un monto válido.');
            return;
        }

        Caja::create([
            'tipo'        => 'entrada',
            'concepto'    => 'Apertura de caja — L ' . number_format((float) $this->montoApertura, 2),
            'monto'       => round((float) $this->montoApertura, 2),
            'factura_id'  => null,
            'mesero_id'   => null,
            'metodo_pago' => 'efectivo',
            'estado'      => 'activo',
        ]);

        // Marcar caja como activa
        CajaActiva::setEstado(true);

        $this->showApertura = false;
        $this->montoApertura = '';
        $this->fecha = today()->toDateString();
    }

    public function with(): array
    {
        $movimientos = Caja::query()
            ->with(['factura.mesa', 'mesero'])
            ->whereDate('created_at', $this->fecha)
            ->where('estado', 'activo')
            ->orderByDesc('created_at')
            ->get();

        $totalEntradas = $movimientos->where('tipo', 'entrada')->sum('monto');
        $totalSalidas  = $movimientos->where('tipo', 'salida')->sum('monto');
        $balance       = $totalEntradas - $totalSalidas;

        $porMetodo = $movimientos
            ->where('tipo', 'entrada')
            ->groupBy('metodo_pago')
            ->map(fn($g) => $g->sum('monto'));

        $estaHoy       = $this->fecha === today()->toDateString();
        $cajaAbierta   = CajaActiva::estaAbierta();
        $cajaCerrada   = $estaHoy && $this->cajaCerradaHoy() && !$cajaAbierta;
        $cierreDatos   = ($estaHoy && $this->cajaCerradaHoy()) ? CajaCierre::whereDate('fecha', $this->fecha)->latest('id')->first() : null;

        return compact(
            'movimientos',
            'totalEntradas',
            'totalSalidas',
            'balance',
            'porMetodo',
            'estaHoy',
            'cajaCerrada',
            'cajaAbierta',
            'cierreDatos'
        );
    }
};
?>

<div class="min-h-screen text-white" style="background:#07090f; font-family:'Courier New', monospace;">

    {{-- HEADER --}}
    <header style="background:#0d1117; border-bottom:1px solid #1a2030;">
             @include('partials.navbar')
        <div class="max-w-screen-xl mx-auto px-6 py-4 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="text-3xl" style="filter:drop-shadow(0 0 10px #10b981);">🏦</div>
                <div>
                    <div class="text-xs tracking-widest uppercase" style="color:#10b981; letter-spacing:.18em;">Control de dinero</div>
                    <div class="font-black text-2xl tracking-tight" style="color:#f1f5f9;">CAJA</div>
                </div>
            </div>


       

            <div class="flex items-center gap-3">
                {{-- Selector de fecha --}}
                <input
                    type="date"
                    wire:model.live="fecha"
                    class="px-4 py-2.5 rounded-xl text-sm outline-none"
                    style="background:#0d1117; border:1px solid #1a2030; color:#e2e8f0;"
                />

                @if($estaHoy)
                    @if($cajaAbierta)
                        <button
                            type="button"
                            wire:click="abrirModalCierre"
                            class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-black transition-all hover:opacity-80"
                            style="background:#ef444422; border:1px solid #ef444444; color:#f87171;"
                        >
                            🔒 Cerrar caja
                        </button>
                    @else
                        <button
                            type="button"
                            wire:click="abrirModalApertura"
                            class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-black transition-all hover:opacity-80"
                            style="background:#10b98122; border:1px solid #10b98144; color:#10b981;"
                        >
                            🔓 Abrir caja
                        </button>
                    @endif
                @endif
            </div>
        </div>
    </header>

    <main class="max-w-screen-xl mx-auto px-4 py-6">

        {{-- Banner caja cerrada --}}
        @if($cajaCerrada && $cierreDatos)
            <div class="flex items-center gap-4 px-5 py-4 rounded-2xl mb-6"
                 style="background:#0f1a0a; border:1px solid #22c55e33;">
                <span class="text-2xl">🔒</span>
                <div class="flex-1">
                    <div class="font-black text-sm" style="color:#22c55e;">Caja cerrada</div>
                    <div class="text-xs mt-0.5" style="color:#475569;">
                        Cerrada el {{ $cierreDatos->cerrada_at->format('d/m/Y') }} a las {{ $cierreDatos->cerrada_at->format('H:i') }}
                        · Balance final: L {{ number_format($cierreDatos->balance, 2) }}
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-xs" style="color:#475569;">Apertura del día</div>
                    <div class="font-black" style="color:#f1f5f9;">L {{ number_format($cierreDatos->apertura, 2) }}</div>
                </div>
            </div>
        @endif

        {{-- ── TARJETAS RESUMEN ──────────────────────────── --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">

            {{-- Entradas --}}
            <div class="rounded-2xl p-5" style="background:#0d1117; border:1px solid #10b98133;">
                <div class="flex items-center justify-between mb-3">
                    <div class="text-xs font-black uppercase tracking-widest" style="color:#10b981;">Total entradas</div>
                    <span class="text-xl">📥</span>
                </div>
                <div class="font-black text-3xl" style="color:#f1f5f9;">L {{ number_format($totalEntradas, 2) }}</div>

                {{-- Desglose por método --}}
                @if($porMetodo->isNotEmpty())
                    <div class="mt-3 space-y-1">
                        @foreach($porMetodo as $metodo => $monto)
                            @php
                                $icon = match($metodo) {
                                    'efectivo'      => '💵',
                                    'transferencia' => '🏦',
                                    'tarjeta'       => '💳',
                                    default         => '💰',
                                };
                            @endphp
                            <div class="flex justify-between text-xs" style="color:#64748b;">
                                <span>{{ $icon }} {{ ucfirst($metodo) }}</span>
                                <span>L {{ number_format($monto, 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Salidas --}}
            <div class="rounded-2xl p-5" style="background:#0d1117; border:1px solid #ef444433;">
                <div class="flex items-center justify-between mb-3">
                    <div class="text-xs font-black uppercase tracking-widest" style="color:#ef4444;">Total salidas</div>
                    <span class="text-xl">📤</span>
                </div>
                <div class="font-black text-3xl" style="color:#f1f5f9;">L {{ number_format($totalSalidas, 2) }}</div>
                <div class="mt-3 text-xs" style="color:#64748b;">
                    {{ $movimientos->where('tipo', 'salida')->count() }} movimiento(s)
                </div>
            </div>

            {{-- Balance --}}
            <div class="rounded-2xl p-5"
                 style="background:#0d1117; border:1px solid {{ $balance >= 0 ? '#10b98133' : '#ef444433' }};">
                <div class="flex items-center justify-between mb-3">
                    <div class="text-xs font-black uppercase tracking-widest"
                         style="color:{{ $balance >= 0 ? '#10b981' : '#ef4444' }};">Balance</div>
                    <span class="text-xl">{{ $balance >= 0 ? '✅' : '⚠️' }}</span>
                </div>
                <div class="font-black text-3xl"
                     style="color:{{ $balance >= 0 ? '#f1f5f9' : '#f87171' }};">
                    {{ $balance >= 0 ? '' : '−' }}L {{ number_format(abs($balance), 2) }}
                </div>
                <div class="mt-3 text-xs" style="color:#64748b;">
                    {{ $movimientos->count() }} movimiento(s) totales
                </div>
            </div>
        </div>

        {{-- ── TABLA DE MOVIMIENTOS ──────────────────────── --}}
        @if($movimientos->isEmpty())
            <div class="flex flex-col items-center justify-center py-32 gap-3" style="color:#334155;">
                <div class="text-6xl opacity-20">🏦</div>
                <div class="text-xl font-bold">Sin movimientos</div>
                <div class="text-sm">No hay entradas ni salidas para esta fecha</div>
            </div>
        @else
            <div class="rounded-2xl overflow-hidden" style="border:1px solid #1a2030;">

                {{-- Cabecera --}}
                <div class="grid text-xs font-black uppercase tracking-widest px-5 py-3"
                     style="grid-template-columns: 0.6fr 2fr 1fr 1fr 0.8fr 1fr;
                            background:#0d1117; border-bottom:1px solid #1a2030; color:#475569;">
                    <div>Tipo</div>
                    <div>Concepto</div>
                    <div>Referencia</div>
                    <div>Método</div>
                    <div>Hora</div>
                    <div class="text-right">Monto</div>
                </div>

                {{-- Filas --}}
                @foreach($movimientos as $mov)
                    @php
                        $esEntrada = $mov->tipo === 'entrada';
                        $iconoMetodo = match($mov->metodo_pago) {
                            'efectivo'      => '💵',
                            'transferencia' => '🏦',
                            'tarjeta'       => '💳',
                            default         => '—',
                        };
                    @endphp
                    <div class="grid items-center px-5 py-3.5 transition-colors hover:bg-white/[0.02]"
                         style="grid-template-columns: 0.6fr 2fr 1fr 1fr 0.8fr 1fr;
                                border-bottom:1px solid #1a2030;">

                        {{-- Tipo --}}
                        <div>
                            @if($esEntrada)
                                <span class="inline-flex items-center gap-1 text-xs font-black px-2 py-1 rounded-lg"
                                      style="background:#10b98122; border:1px solid #10b98133; color:#10b981;">
                                    ↑ Entrada
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-xs font-black px-2 py-1 rounded-lg"
                                      style="background:#ef444422; border:1px solid #ef444433; color:#f87171;">
                                    ↓ Salida
                                </span>
                            @endif
                        </div>

                        {{-- Concepto --}}
                        <div>
                            <div class="text-sm font-semibold truncate" style="color:#e2e8f0;">
                                {{ $mov->concepto }}
                            </div>
                            @if($mov->mesero)
                                <div class="text-xs mt-0.5" style="color:#334155;">
                                    👤 {{ $mov->mesero->nombre }}
                                </div>
                            @endif
                        </div>

                        {{-- Referencia (N° factura si existe) --}}
                        <div class="text-xs font-mono" style="color:#475569;">
                            @if($mov->factura)
                                {{ $mov->factura->numero_factura ?? '—' }}
                            @else
                                —
                            @endif
                        </div>

                        {{-- Método --}}
                        <div class="text-sm" style="color:#64748b;">
                            @if($mov->metodo_pago)
                                {{ $iconoMetodo }} {{ ucfirst($mov->metodo_pago) }}
                            @else
                                <span style="color:#334155;">—</span>
                            @endif
                        </div>

                        {{-- Hora --}}
                        <div class="text-sm font-mono" style="color:#475569;">
                            {{ $mov->created_at->format('H:i') }}
                        </div>

                        {{-- Monto --}}
                        <div class="text-right font-black text-base"
                             style="color:{{ $esEntrada ? '#10b981' : '#f87171' }};">
                            {{ $esEntrada ? '+' : '−' }}L {{ number_format($mov->monto, 2) }}
                        </div>
                    </div>
                @endforeach

                {{-- Fila de totales --}}
                <div class="grid items-center px-5 py-4"
                     style="grid-template-columns: 0.6fr 2fr 1fr 1fr 0.8fr 1fr;
                            background:#0d1117; border-top:2px solid #1a2030;">
                    <div></div>
                    <div class="font-black text-sm" style="color:#475569;">TOTALES</div>
                    <div></div>
                    <div></div>
                    <div></div>
                    <div class="text-right">
                        <div class="text-xs font-bold" style="color:#10b981;">
                            +L {{ number_format($totalEntradas, 2) }}
                        </div>
                        <div class="text-xs font-bold" style="color:#f87171;">
                            −L {{ number_format($totalSalidas, 2) }}
                        </div>
                        <div class="text-sm font-black mt-1 pt-1" style="color:#f1f5f9; border-top:1px solid #1a2030;">
                            L {{ number_format($balance, 2) }}
                        </div>
                    </div>
                </div>

            </div>
        @endif
    </main>

    {{-- ── MODAL: CONFIRMAR CIERRE ──────────────────────────────── --}}
    @if($showCierre)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/80" wire:click="cancelarCierre"></div>

            <div class="relative w-full max-w-sm rounded-2xl p-7 flex flex-col gap-5"
                 style="background:#0d1117; border:1.5px solid #ef444444;
                        box-shadow:0 0 60px #ef444415, 0 24px 48px rgba(0,0,0,0.6);">

                <div class="flex justify-center">
                    <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-3xl"
                         style="background:#ef444422; border:1.5px solid #ef444444;">
                        🔒
                    </div>
                </div>

                <div class="text-center">
                    <div class="font-black text-lg mb-1" style="color:#f1f5f9;">¿Cerrar caja?</div>
                    <div class="text-sm" style="color:#475569;">
                        Todos los movimientos activos de hoy quedarán cerrados y no se podrán modificar.
                    </div>
                </div>

                {{-- Resumen rápido --}}
                <div class="rounded-xl px-4 py-4 space-y-2" style="background:#111827; border:1px solid #1a2030;">
                    <div class="flex justify-between text-sm">
                        <span style="color:#64748b;">Entradas</span>
                        <span class="font-bold" style="color:#10b981;">+L {{ number_format($totalEntradas, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span style="color:#64748b;">Salidas</span>
                        <span class="font-bold" style="color:#f87171;">−L {{ number_format($totalSalidas, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm pt-2" style="border-top:1px solid #1a2030;">
                        <span class="font-black" style="color:#f1f5f9;">Balance</span>
                        <span class="font-black text-lg" style="color:#f1f5f9;">L {{ number_format($balance, 2) }}</span>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button
                        type="button"
                        wire:click="cancelarCierre"
                        class="flex-1 py-3 rounded-xl font-bold text-sm transition-all hover:opacity-80"
                        style="background:#1a2030; color:#94a3b8; border:1.5px solid #2a3441;"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        wire:click="confirmarCierre"
                        class="flex-1 py-3 rounded-xl font-black text-sm transition-all active:scale-[0.98]"
                        style="background:#ef444422; color:#f87171; border:1.5px solid #ef444455;"
                    >
                        🔒 Confirmar cierre
                    </button>
                </div>

                <div class="text-xs text-center" style="color:#334155;">Toca afuera para cancelar</div>
            </div>
        </div>
    @endif

    {{-- ── MODAL: APERTURA DE CAJA ──────────────────────────────── --}}
    @if($showApertura)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/80" wire:click="cancelarApertura"></div>

            <div class="relative w-full max-w-sm rounded-2xl p-7 flex flex-col gap-5"
                 style="background:#0d1117; border:1.5px solid #10b98144;
                        box-shadow:0 0 60px #10b98115, 0 24px 48px rgba(0,0,0,0.6);">

                <div class="flex justify-center">
                    <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-3xl"
                         style="background:#10b98122; border:1.5px solid #10b98144;">
                        🔓
                    </div>
                </div>

                <div class="text-center">
                    <div class="font-black text-lg mb-1" style="color:#f1f5f9;">Abrir caja</div>
                    <div class="text-sm" style="color:#475569;">¿Con cuánto dinero inicia la caja hoy?</div>
                </div>

                <div>
                    <label class="block text-xs font-black uppercase tracking-widest mb-2" style="color:#64748b;">
                        Monto de apertura
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 font-black text-sm"
                              style="color:#475569;">L</span>
                        <input
                            type="number"
                            inputmode="decimal"
                            min="0"
                            step="0.01"
                            wire:model.live="montoApertura"
                            placeholder="0.00"
                            class="w-full pl-9 pr-4 py-4 rounded-xl text-2xl font-black outline-none transition-all
                                   [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                            style="background:#111827; border:1.5px solid {{ $errors->has('montoApertura') ? '#ef4444' : '#1a2030' }}; color:#f1f5f9;"
                            autofocus
                        />
                    </div>
                    @error('montoApertura')
                        <div class="text-xs mt-1.5 font-semibold" style="color:#f87171;">{{ $message }}</div>
                    @enderror
                    <div class="text-xs mt-2" style="color:#334155;">
                        Este monto se registrará como entrada de apertura en caja.
                    </div>
                </div>

                <div class="flex gap-3">
                    <button
                        type="button"
                        wire:click="cancelarApertura"
                        class="flex-1 py-3 rounded-xl font-bold text-sm transition-all hover:opacity-80"
                        style="background:#1a2030; color:#94a3b8; border:1.5px solid #2a3441;"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        wire:click="confirmarApertura"
                        class="flex-1 py-3 rounded-xl font-black text-sm transition-all active:scale-[0.98]"
                        style="background:#10b98122; color:#10b981; border:1.5px solid #10b98144;"
                    >
                        🔓 Abrir caja
                    </button>
                </div>

                <div class="text-xs text-center" style="color:#334155;">Toca afuera para cancelar</div>
            </div>
        </div>
    @endif

</div>
