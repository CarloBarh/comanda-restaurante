<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Factura;

new class extends Component
{
    use WithPagination;

    public string $busqueda = '';
    public string $tipoPago = '';
    public string $fecha = '';

    public function updatingBusqueda(): void { $this->resetPage(); }
    public function updatingTipoPago(): void { $this->resetPage(); }
    public function updatingFecha(): void    { $this->resetPage(); }

    public function with(): array
    {
        $facturas = Factura::query()
            ->with(['mesa', 'mesero'])
            ->when($this->busqueda, fn($q) =>
                $q->where('numero_factura', 'like', '%'.$this->busqueda.'%')
                  ->orWhere('cliente_nombre', 'like', '%'.$this->busqueda.'%')
                  ->orWhere('cliente_rtn', 'like', '%'.$this->busqueda.'%')
                  ->orWhereHas('mesa', fn($q2) => $q2->where('numero', 'like', '%'.$this->busqueda.'%'))
                  ->orWhereHas('mesero', fn($q2) => $q2->where('nombre', 'like', '%'.$this->busqueda.'%'))
            )
            ->when($this->tipoPago, fn($q) => $q->where('tipo_pago', $this->tipoPago))
            ->when($this->fecha, fn($q) => $q->whereDate('created_at', $this->fecha))
            ->orderByDesc('created_at')
            ->paginate(15);

        $totalHoy = Factura::whereDate('created_at', today())->sum('total');
        $totalMes = Factura::whereMonth('created_at', now()->month)
                           ->whereYear('created_at', now()->year)
                           ->sum('total');

        return compact('facturas', 'totalHoy', 'totalMes');
    }
};
?>

<div class="min-h-screen text-white" style="background:#07090f; font-family: 'Courier New', monospace;">

    {{-- HEADER --}}
    <header style="background:#0d1117; border-bottom:1px solid #1a2030;">
             @include('partials.navbar')
        <div class="max-w-screen-xl mx-auto px-6 py-4 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="text-3xl" style="filter:drop-shadow(0 0 10px #10b981);">🧾</div>
                <div>
                    <div class="text-xs tracking-widest uppercase" style="color:#10b981; letter-spacing:.18em;">Historial</div>
                    <div class="font-black text-2xl tracking-tight" style="color:#f1f5f9;">FACTURAS</div>
                </div>
            </div>

       

            <div class="flex gap-3">
                <div class="px-4 py-2 rounded-xl text-right" style="background:#0f1f18; border:1px solid #10b98133;">
                    <div class="text-xs" style="color:#10b981;">Hoy</div>
                    <div class="font-black text-lg" style="color:#f1f5f9;">L {{ number_format($totalHoy, 2) }}</div>
                </div>
                <div class="px-4 py-2 rounded-xl text-right" style="background:#0f1a2a; border:1px solid #3b82f633;">
                    <div class="text-xs" style="color:#3b82f6;">Este mes</div>
                    <div class="font-black text-lg" style="color:#f1f5f9;">L {{ number_format($totalMes, 2) }}</div>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-screen-xl mx-auto px-4 py-6">

        {{-- FILTROS --}}
        <div class="flex flex-wrap gap-3 mb-6">
            <div class="relative flex-1 min-w-48">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm" style="color:#475569;">🔍</span>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="busqueda"
                    placeholder="N° factura, cliente, RTN, mesa, mesero..."
                    class="w-full pl-8 pr-4 py-2.5 rounded-xl text-sm outline-none"
                    style="background:#0d1117; border:1px solid #1a2030; color:#e2e8f0;"
                />
            </div>

            <select
                wire:model.live="tipoPago"
                class="px-4 py-2.5 rounded-xl text-sm outline-none"
                style="background:#0d1117; border:1px solid #1a2030; color:#e2e8f0;"
            >
                <option value="">Todos los pagos</option>
                <option value="efectivo">💵 Efectivo</option>
                <option value="transferencia">🏦 Transferencia</option>
                <option value="tarjeta">💳 Tarjeta</option>
            </select>

            <input
                type="date"
                wire:model.live="fecha"
                class="px-4 py-2.5 rounded-xl text-sm outline-none"
                style="background:#0d1117; border:1px solid #1a2030; color:#e2e8f0;"
            />
        </div>

        {{-- TABLA --}}
        @if($facturas->isEmpty())
            <div class="flex flex-col items-center justify-center py-32 gap-3" style="color:#334155;">
                <div class="text-6xl opacity-20">🧾</div>
                <div class="text-xl font-bold">Sin facturas</div>
                <div class="text-sm">No hay resultados para los filtros aplicados</div>
            </div>
        @else
            <div class="rounded-2xl overflow-hidden" style="border:1px solid #1a2030;">

                <div class="grid text-xs font-black uppercase tracking-widest px-5 py-3"
                     style="grid-template-columns: 1.4fr 1.2fr 0.7fr 1fr 1fr 0.8fr 0.8fr auto;
                            background:#0d1117; border-bottom:1px solid #1a2030; color:#475569;">
                    <div>N° Factura</div>
                    <div>Cliente</div>
                    <div>Mesa</div>
                    <div>Mesero</div>
                    <div>Fecha</div>
                    <div>Tipo pago</div>
                    <div class="text-right">Total</div>
                    <div></div>
                </div>

                @foreach($facturas as $f)
                    @php
                        $iconoPago = match($f->tipo_pago) {
                            'efectivo'      => '💵',
                            'transferencia' => '🏦',
                            'tarjeta'       => '💳',
                            default         => '💰',
                        };
                        $colorPago = match($f->tipo_pago) {
                            'efectivo'      => ['bg' => '#0f1f0f', 'border' => '#22c55e33', 'text' => '#22c55e'],
                            'transferencia' => ['bg' => '#0a0f1f', 'border' => '#3b82f633', 'text' => '#3b82f6'],
                            'tarjeta'       => ['bg' => '#150a1f', 'border' => '#a855f733', 'text' => '#a855f7'],
                            default         => ['bg' => '#0d1117', 'border' => '#1a203088', 'text' => '#94a3b8'],
                        };
                    @endphp
                    <div class="grid items-center px-5 py-3.5 transition-colors hover:bg-white/[0.02]"
                         style="grid-template-columns: 1.4fr 1.2fr 0.7fr 1fr 1fr 0.8fr 0.8fr auto;
                                border-bottom:1px solid #1a2030;">

                        <div>
                            <div class="font-mono text-sm font-bold" style="color:#e2e8f0;">
                                {{ $f->numero_factura ?? '—' }}
                            </div>
                            <div class="text-xs" style="color:#334155;">#{{ str_pad($f->id, 4, '0', STR_PAD_LEFT) }}</div>
                        </div>

                        {{-- Cliente --}}
                        <div>
                            <div class="text-sm font-semibold truncate" style="color:#cbd5e1;">
                                {{ $f->cliente_nombre ?? 'Consumidor Final' }}
                            </div>
                            @if($f->cliente_rtn)
                                <div class="text-xs font-mono mt-0.5" style="color:#475569;">
                                    RTN: {{ $f->cliente_rtn }}
                                </div>
                            @endif
                        </div>

                        <div>
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg font-black text-sm"
                                  style="background:#10b98122; border:1px solid #10b98144; color:#10b981;">
                                {{ $f->mesa?->numero ?? '?' }}
                            </span>
                        </div>

                        <div class="text-sm" style="color:#cbd5e1;">
                            {{ $f->mesero?->nombre ?? '—' }}
                        </div>

                        <div>
                            <div class="text-sm" style="color:#cbd5e1;">{{ $f->created_at->format('d/m/Y') }}</div>
                            <div class="text-xs" style="color:#334155;">{{ $f->created_at->format('H:i') }}</div>
                        </div>

                        <div>
                            <span class="inline-flex items-center gap-1.5 text-xs font-bold px-2.5 py-1 rounded-lg"
                                  style="background:{{ $colorPago['bg'] }}; border:1px solid {{ $colorPago['border'] }}; color:{{ $colorPago['text'] }};">
                                {{ $iconoPago }} {{ ucfirst($f->tipo_pago) }}
                            </span>
                        </div>

                        <div class="text-right">
                            <div class="font-black" style="color:#f1f5f9;">L {{ number_format($f->total, 2) }}</div>
                        </div>

                        <div class="pl-4">
                            <a
                                href="{{ route('facturas.detalle', $f->id) }}"
                                class="text-xs px-3 py-2 rounded-xl font-bold transition-all hover:opacity-80 inline-block"
                                style="background:#10b98122; border:1px solid #10b98144; color:#10b981;"
                            >
                                Ver detalles
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $facturas->links() }}
            </div>
        @endif
    </main>
</div>
