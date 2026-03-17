<?php

use Livewire\Component;
use App\Models\Caja;

new class extends Component
{
    public string $monto = '';
    public string $concepto = '';
    public bool $showConfirmacion = false;

    public function abrirConfirmacion(): void
    {
        $this->resetErrorBag();

        // Validar monto
        if (trim($this->monto) === '' || !is_numeric($this->monto) || (float) $this->monto <= 0) {
            $this->addError('monto', 'Ingresa un monto válido mayor a 0.');
            return;
        }

        if (trim($this->concepto) === '') {
            $this->addError('concepto', 'El concepto es obligatorio.');
            return;
        }

        $this->showConfirmacion = true;
    }

    public function confirmarSalida(): void
    {
        Caja::create([
            'tipo'        => 'salida',
            'concepto'    => trim($this->concepto),
            'monto'       => round((float) $this->monto, 2),
            'factura_id'  => null,
            'mesero_id'   => null,
            'metodo_pago' => null,
            'estado'      => 'activo',
        ]);

        $this->monto    = '';
        $this->concepto = '';
        $this->showConfirmacion = false;

        session()->flash('exito', 'Salida registrada correctamente.');
    }

    public function cancelarConfirmacion(): void
    {
        $this->showConfirmacion = false;
    }

    public function with(): array
    {
        // Últimas 10 salidas del día para mostrar en el historial rápido
        $salidas = Caja::where('tipo', 'salida')
            ->where('estado', 'activo')
            ->whereDate('created_at', today())
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $totalSalidasHoy = Caja::where('tipo', 'salida')
            ->where('estado', 'activo')
            ->whereDate('created_at', today())
            ->sum('monto');

        return compact('salidas', 'totalSalidasHoy');
    }
};
?>

<div class="min-h-screen text-white" style="background:#07090f; font-family:'Courier New', monospace;">

    {{-- HEADER --}}
    <header style="background:#0d1117; border-bottom:1px solid #1a2030;">
             @include('partials.navbar')
        <div class="max-w-screen-xl mx-auto px-6 py-4 flex items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="text-3xl" style="filter:drop-shadow(0 0 10px #ef4444);">💸</div>
                <div>
                    <div class="text-xs tracking-widest uppercase" style="color:#ef4444; letter-spacing:.18em;">Control de caja</div>
                    <div class="font-black text-2xl tracking-tight" style="color:#f1f5f9;">GASTOS</div>
                </div>

             
            </div>


            <div class="px-4 py-2 rounded-xl text-right" style="background:#1f0a0a; border:1px solid #ef444433;">
                <div class="text-xs" style="color:#ef4444;">Total salidas hoy</div>
                <div class="font-black text-xl" style="color:#f1f5f9;">L {{ number_format($totalSalidasHoy, 2) }}</div>
            </div>
        </div>
    </header>

    <main class="max-w-screen-xl mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">

            {{-- ── FORMULARIO ────────────────────────────────── --}}
            <div class="rounded-2xl p-8" style="background:#0d1117; border:1px solid #1a2030;">

                <div class="mb-7">
                    <div class="text-xs tracking-widest uppercase mb-1" style="color:#475569;">Nueva salida</div>
                    <div class="font-black text-xl" style="color:#f1f5f9;">Registrar gasto</div>
                </div>

                {{-- Flash de éxito --}}
                @if(session('exito'))
                    <div class="flex items-center gap-3 px-4 py-3 rounded-xl mb-6 text-sm font-semibold"
                         style="background:#0f1f0f; border:1px solid #22c55e44; color:#22c55e;">
                        <span>✅</span>
                        <span>{{ session('exito') }}</span>
                    </div>
                @endif

                <div class="space-y-5">

                    {{-- Concepto --}}
                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest mb-2" style="color:#64748b;">
                            Concepto <span style="color:#ef4444;">*</span>
                        </label>
                        <input
                            type="text"
                            wire:model.live="concepto"
                            placeholder="Ej: Compra de insumos, pago de servicio..."
                            class="w-full px-4 py-3.5 rounded-xl text-sm outline-none transition-all"
                            style="background:#111827; border:1.5px solid {{ $errors->has('concepto') ? '#ef4444' : '#1a2030' }}; color:#f1f5f9;"
                        />
                        @error('concepto')
                            <div class="text-xs mt-1.5 font-semibold" style="color:#f87171;">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Monto --}}
                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest mb-2" style="color:#64748b;">
                            Monto <span style="color:#ef4444;">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 font-black text-sm"
                                  style="color:#475569;">L</span>
                            <input
                                type="number"
                                inputmode="decimal"
                                min="0.01"
                                step="0.01"
                                wire:model.live="monto"
                                placeholder="0.00"
                                class="w-full pl-9 pr-4 py-4 rounded-xl text-2xl font-black outline-none transition-all
                                       [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                style="background:#111827; border:1.5px solid {{ $errors->has('monto') ? '#ef4444' : '#1a2030' }}; color:#f1f5f9;"
                            />
                        </div>
                        @error('monto')
                            <div class="text-xs mt-1.5 font-semibold" style="color:#f87171;">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Atajos rápidos de montos --}}
                    <div>
                        <div class="text-xs mb-2" style="color:#334155;">Atajos rápidos</div>
                        <div class="flex flex-wrap gap-2">
                            @foreach([50, 100, 200, 500, 1000] as $val)
                                <button
                                    type="button"
                                    wire:click="$set('monto', '{{ $val }}')"
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all hover:opacity-80 active:scale-95"
                                    style="background:#1a2030; border:1px solid #2a3441; color:#94a3b8;"
                                >
                                    L {{ number_format($val) }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Botón registrar --}}
                    <button
                        type="button"
                        wire:click="abrirConfirmacion"
                        class="w-full py-4 rounded-xl font-black text-base transition-all active:scale-[0.99] mt-2"
                        style="background:#ef444422; border:1.5px solid #ef444444; color:#f87171;"
                    >
                        💸 Registrar salida
                    </button>
                </div>
            </div>

            {{-- ── HISTORIAL DEL DÍA ─────────────────────────── --}}
            <div>
                <div class="text-xs font-black uppercase tracking-widest mb-4" style="color:#475569;">
                    Salidas de hoy
                </div>

                @if($salidas->isEmpty())
                    <div class="flex flex-col items-center justify-center py-16 rounded-2xl gap-3"
                         style="background:#0d1117; border:1px solid #1a2030; color:#334155;">
                        <div class="text-5xl opacity-30">💸</div>
                        <div class="text-sm font-bold">Sin salidas registradas hoy</div>
                    </div>
                @else
                    <div class="space-y-2">
                        @foreach($salidas as $s)
                            <div class="flex items-center justify-between gap-4 px-4 py-3.5 rounded-xl"
                                 style="background:#0d1117; border:1px solid #1a2030;">
                                <div class="min-w-0 flex-1">
                                    <div class="font-semibold text-sm truncate" style="color:#e2e8f0;">
                                        {{ $s->concepto }}
                                    </div>
                                    <div class="text-xs mt-0.5" style="color:#334155;">
                                        {{ $s->created_at->format('H:i') }}
                                    </div>
                                </div>
                                <div class="font-black text-base flex-shrink-0" style="color:#f87171;">
                                    −L {{ number_format($s->monto, 2) }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
    </main>

    {{-- ── MODAL DE CONFIRMACIÓN ─────────────────────────────── --}}
    @if($showConfirmacion)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/80" wire:click="cancelarConfirmacion"></div>

            <div class="relative w-full max-w-sm rounded-2xl p-7 flex flex-col gap-5"
                 style="background:#0d1117; border:1.5px solid #ef444444;
                        box-shadow:0 0 60px #ef444415, 0 24px 48px rgba(0,0,0,0.6);">

                {{-- Icono --}}
                <div class="flex justify-center">
                    <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-3xl"
                         style="background:#ef444422; border:1.5px solid #ef444444;">
                        ⚠️
                    </div>
                </div>

                {{-- Texto --}}
                <div class="text-center">
                    <div class="font-black text-lg mb-1" style="color:#f1f5f9;">¿Confirmar salida?</div>
                    <div class="text-sm" style="color:#475569;">Esta acción registrará el siguiente gasto en caja</div>
                </div>

                {{-- Resumen --}}
                <div class="rounded-xl px-4 py-4 space-y-2" style="background:#111827; border:1px solid #1a2030;">
                    <div class="flex justify-between text-sm">
                        <span style="color:#64748b;">Concepto</span>
                        <span class="font-semibold text-right max-w-[60%] truncate" style="color:#e2e8f0;">{{ $concepto }}</span>
                    </div>
                    <div class="flex justify-between items-center pt-1" style="border-top:1px solid #1a2030;">
                        <span class="font-black" style="color:#64748b;">Monto</span>
                        <span class="font-black text-2xl" style="color:#f87171;">−L {{ number_format((float)$monto, 2) }}</span>
                    </div>
                </div>

                {{-- Botones --}}
                <div class="flex gap-3">
                    <button
                        type="button"
                        wire:click="cancelarConfirmacion"
                        class="flex-1 py-3 rounded-xl font-bold text-sm transition-all hover:opacity-80"
                        style="background:#1a2030; color:#94a3b8; border:1.5px solid #2a3441;"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        wire:click="confirmarSalida"
                        class="flex-1 py-3 rounded-xl font-black text-sm transition-all active:scale-[0.98]"
                        style="background:#ef444422; color:#f87171; border:1.5px solid #ef444455;"
                    >
                        💸 Confirmar
                    </button>
                </div>

                <div class="text-xs text-center" style="color:#334155;">
                    Toca afuera para cancelar
                </div>
            </div>
        </div>
    @endif

</div>
