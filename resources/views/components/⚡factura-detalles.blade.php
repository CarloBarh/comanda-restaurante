<?php

use Livewire\Component;
use App\Models\Factura;

new class extends Component
{
    public int $facturaId;

    public function mount(int $factura): void
    {
        $this->facturaId = $factura;
    }

    public function with(): array
    {
        $factura = Factura::with(['mesa', 'mesero', 'detalles'])
            ->findOrFail($this->facturaId);

        return compact('factura');
    }
};
?>

<div class="min-h-screen text-white" style="background:#07090f; font-family: 'Courier New', monospace;">

    {{-- HEADER --}}
    <header style="background:#0d1117; border-bottom:1px solid #1a2030;">
             @include('partials.navbar')
        <div class="max-w-screen-xl mx-auto px-6 py-4 flex items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('facturas') }}"
                   class="w-10 h-10 rounded-xl flex items-center justify-center transition-colors hover:bg-white/10"
                   style="background:#1a2030; color:#94a3b8;">
                    ←
                </a>
                <div>
                    <div class="text-xs tracking-widest uppercase" style="color:#10b981; letter-spacing:.18em;">Detalle</div>
                    <div class="font-black text-2xl tracking-tight" style="color:#f1f5f9;">
                        {{ $factura->numero_factura ?? 'Factura #'.str_pad($factura->id, 4, '0', STR_PAD_LEFT) }}
                    </div>
                </div>
            </div>

            {{-- Info rápida --}}
            <div class="flex flex-wrap items-center gap-3 text-sm" style="color:#64748b;">
                <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg font-black"
                      style="background:#10b98122; border:1px solid #10b98144; color:#10b981;">
                    {{ $factura->mesa?->numero ?? '?' }}
                </span>
                <span>👤 {{ $factura->mesero?->nombre ?? '—' }}</span>
                <span>📅 {{ $factura->created_at->format('d/m/Y H:i') }}</span>
                @php
                    $iconoPago = match($factura->tipo_pago) {
                        'efectivo'      => '💵',
                        'transferencia' => '🏦',
                        'tarjeta'       => '💳',
                        default         => '💰',
                    };
                    $colorPago = match($factura->tipo_pago) {
                        'efectivo'      => ['bg' => '#0f1f0f', 'border' => '#22c55e33', 'text' => '#22c55e'],
                        'transferencia' => ['bg' => '#0a0f1f', 'border' => '#3b82f633', 'text' => '#3b82f6'],
                        'tarjeta'       => ['bg' => '#150a1f', 'border' => '#a855f733', 'text' => '#a855f7'],
                        default         => ['bg' => '#0d1117', 'border' => '#1a203088', 'text' => '#94a3b8'],
                    };
                @endphp
                <span class="inline-flex items-center gap-1.5 text-xs font-bold px-2.5 py-1.5 rounded-lg"
                      style="background:{{ $colorPago['bg'] }}; border:1px solid {{ $colorPago['border'] }}; color:{{ $colorPago['text'] }};">
                    {{ $iconoPago }} {{ ucfirst($factura->tipo_pago) }}
                </span>
            </div>
        </div>
    </header>

    <main class="max-w-screen-xl mx-auto px-4 py-6">

        <div class="rounded-2xl overflow-hidden mb-6" style="border:1px solid #1a2030;">

            {{-- Cabecera tabla --}}
            <div class="grid text-xs font-black uppercase tracking-widest px-5 py-3"
                 style="grid-template-columns: 2fr 0.5fr 0.9fr 0.9fr 0.6fr 1fr;
                        background:#0d1117; border-bottom:1px solid #1a2030; color:#475569;">
                <div>Platillo</div>
                <div class="text-center">Cant.</div>
                <div class="text-right">P/U</div>
                <div class="text-right">Bruto</div>
                <div class="text-center">Desc.</div>
                <div class="text-right">Subtotal</div>
            </div>

            {{-- Filas --}}
            @foreach($factura->detalles as $det)
                @php
                    $bruto          = $det->precio_unitario * $det->cantidad;
                    $tieneDescuento = ($det->descuento ?? 0) > 0;
                @endphp
                <div class="grid items-center px-5 py-4"
                     style="grid-template-columns: 2fr 0.5fr 0.9fr 0.9fr 0.6fr 1fr;
                            border-bottom:1px solid #1a2030;
                            background:{{ $tieneDescuento ? '#120808' : 'transparent' }};">

                    {{-- Nombre --}}
                    <div>
                        <div class="font-semibold text-sm" style="color:#e2e8f0;">
                            {{ $det->platillo_nombre }}
                        </div>
                        @if($det->tamano_nombre)
                            <div class="text-xs mt-0.5" style="color:#475569;">{{ $det->tamano_nombre }}</div>
                        @endif
                        @if($det->notas)
                            <div class="text-xs mt-1 italic" style="color:#64748b;">📝 {{ $det->notas }}</div>
                        @endif
                    </div>

                    {{-- Cantidad --}}
                    <div class="text-center font-black" style="color:#cbd5e1;">
                        {{ $det->cantidad }}
                    </div>

                    {{-- Precio unitario --}}
                    <div class="text-right text-sm" style="color:#94a3b8;">
                        L {{ number_format($det->precio_unitario, 2) }}
                    </div>

                    {{-- Bruto (tachado si tiene descuento) --}}
                    <div class="text-right text-sm"
                         style="color:{{ $tieneDescuento ? '#475569' : '#94a3b8' }};
                                {{ $tieneDescuento ? 'text-decoration:line-through;' : '' }}">
                        L {{ number_format($bruto, 2) }}
                    </div>

                    {{-- Descuento --}}
                    <div class="text-center">
                        @if($tieneDescuento)
                            <div class="inline-flex flex-col items-center gap-0.5">
                                <span class="text-xs font-black px-2 py-0.5 rounded-lg"
                                      style="background:#ef444422; border:1px solid #ef444433; color:#f87171;">
                                    −{{ $det->descuento }}%
                                </span>
                                <span class="text-xs font-bold" style="color:#f87171;">
                                    −L {{ number_format($det->monto_descuento, 2) }}
                                </span>
                            </div>
                        @else
                            <span style="color:#1e2a3a;">—</span>
                        @endif
                    </div>

                    {{-- Subtotal --}}
                    <div class="text-right font-black" style="color:#f1f5f9;">
                        L {{ number_format($det->subtotal, 2) }}
                    </div>
                </div>
            @endforeach
        </div>

        {{-- TOTALES --}}
        @php
            $totalDescuentos = $factura->detalles->sum('monto_descuento');
        @endphp
        <div class="ml-auto max-w-sm rounded-2xl p-5 space-y-3"
             style="background:#0d1117; border:1px solid #1a2030;">

            <div class="flex justify-between text-sm" style="color:#475569;">
                <span>Importe exonerado</span>
                <span>L {{ number_format($factura->importe_exonerado, 2) }}</span>
            </div>
            <div class="flex justify-between text-sm" style="color:#475569;">
                <span>Importe exento</span>
                <span>L {{ number_format($factura->importe_exento, 2) }}</span>
            </div>
            <div class="flex justify-between text-sm" style="color:#94a3b8;">
                <span>Importe gravado 15%</span>
                <span>L {{ number_format($factura->base_gravada, 2) }}</span>
            </div>
            <div class="flex justify-between text-sm" style="color:#94a3b8;">
                <span>ISV 15%</span>
                <span>L {{ number_format($factura->isv_15, 2) }}</span>
            </div>

            @if($totalDescuentos > 0)
                <div class="flex justify-between text-sm font-bold" style="color:#f87171;">
                    <span>Total descuentos</span>
                    <span>−L {{ number_format($totalDescuentos, 2) }}</span>
                </div>
            @endif

            <div class="flex justify-between items-center pt-3"
                 style="border-top:1px solid #1a2030;">
                <span class="font-black text-lg" style="color:#10b981;">TOTAL PAGADO</span>
                <span class="font-black text-3xl" style="color:#f1f5f9;">
                    L {{ number_format($factura->total, 2) }}
                </span>
            </div>
        </div>

    </main>
</div>
