<?php

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use App\Models\Comanda;
use App\Models\ComandaDetalle;
use App\Models\Mesa;
use App\Models\Factura;
use App\Models\FacturaDetalle;
use App\Models\Caja;
use App\Models\CajaActiva;

new class extends Component
{
    public bool $showPago = false;
    public ?int $comandaAPagar = null;
    public string $tipoPago = '';
    public bool $showCajaCerrada = false;

    // ── Paso del modal ──────────────────────────────────────────
    // 'pago' = elegir tipo de pago | 'cliente' = datos del cliente
    public string $paso = 'pago';
    public string $clienteNombre = '';
    public string $clienteRtn = '';

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

    public function abrirModalPago(int $comandaId): void
    {
        // Verificar si la caja está abierta
        if (!CajaActiva::estaAbierta()) {
            $this->showCajaCerrada = true;
            return;
        }

        $this->comandaAPagar = $comandaId;
        $this->tipoPago = '';
        $this->paso = 'pago';
        $this->clienteNombre = '';
        $this->clienteRtn = '';
        $this->showPago = true;
    }

    public function avanzarACliente(): void
    {
        if (!$this->tipoPago) return;
        $this->paso = 'cliente';
    }

    public function volverAPago(): void
    {
        $this->paso = 'pago';
    }

    public function realizarPago(): void
    {
        if (!$this->comandaAPagar || !$this->tipoPago) return;

        // Validar nombre obligatorio
        if (trim($this->clienteNombre) === '') {
            $this->addError('clienteNombre', 'El nombre del cliente es obligatorio.');
            return;
        }

        $comanda = Comanda::with(['mesa', 'mesero', 'detalles.platillo', 'detalles.tamano'])
            ->findOrFail($this->comandaAPagar);

        $total       = (float) $comanda->total;
        $baseGravada = round($total / 1.15, 2);
        $isv15       = round($total - $baseGravada, 2);

        DB::transaction(function () use ($comanda, $total, $baseGravada, $isv15) {

            // 1) Crear registro en facturas
            $factura = Factura::create([
                'comanda_id'          => $comanda->id,
                'mesa_id'             => $comanda->mesa_id,
                'mesero_id'           => $comanda->mesero_id,
                'tipo_pago'           => $this->tipoPago,
                'cliente_nombre'      => trim($this->clienteNombre),
                'cliente_rtn'         => trim($this->clienteRtn) ?: null,
                'total'               => $total,
                'base_gravada'        => $baseGravada,
                'isv_15'              => $isv15,
                'importe_exento'      => 0,
                'importe_exonerado'   => 0,
                'numero_factura'      => null,
            ]);

            $factura->update([
                'numero_factura' => $factura->generarNumero(),
            ]);

            // 2) Insertar detalles de factura (snapshot)
            foreach ($comanda->detalles as $detalle) {
                FacturaDetalle::create([
                    'factura_id'      => $factura->id,
                    'platillo_id'     => $detalle->platillo_id,
                    'tamano_id'       => $detalle->tamano_id,
                    'platillo_nombre' => $detalle->platillo?->nombre ?? 'Platillo',
                    'tamano_nombre'   => $detalle->tamano?->nombre,
                    'cantidad'        => (int) $detalle->cantidad,
                    'precio_unitario' => (float) $detalle->precio_unitario,
                    'subtotal'        => (float) $detalle->subtotal,
                    'notas'           => $detalle->notas,
                    'descuento'       => (int) ($detalle->descuento ?? 0),
                    'monto_descuento' => (float) ($detalle->monto_descuento ?? 0),
                ]);
            }

            // 3) Finalizar comanda y liberar mesa
            $comanda->update([
                'estado'    => 'finalizado',
                'tipo_pago' => $this->tipoPago,
            ]);

            ComandaDetalle::where('comanda_id', $comanda->id)
                ->update(['estado' => 'listo']);

            Mesa::whereKey($comanda->mesa_id)
                ->update(['estado' => 'libre']);

            // 4) Registrar entrada en caja
            Caja::create([
                'tipo'        => 'entrada',
                'concepto'    => 'Venta mesa ' . ($comanda->mesa?->numero ?? '?') . ' — ' . ($this->clienteNombre ?: 'Consumidor Final'),
                'monto'       => $total,
                'factura_id'  => $factura->id,
                'mesero_id'   => $comanda->mesero_id,
                'metodo_pago' => $this->tipoPago,
                'estado'      => 'activo',
            ]);

            // 5) Enviar datos al JS para imprimir el PDF
            $facturaData = json_encode([
                'comanda_id'     => $comanda->id,
                'factura_id'     => $factura->id,
                'numero_factura' => $factura->numero_factura,
                'mesa'           => $comanda->mesa?->numero ?? '?',
                'mesero'         => $comanda->mesero?->nombre ?? '—',
                'cliente'        => trim($this->clienteNombre),
                'rtn'            => trim($this->clienteRtn) ?: null,
                'tipo_pago'      => $this->tipoPago,
                'fecha'          => now()->format('d/m/Y H:i'),
                'total'          => $total,
                'base_gravada'   => $baseGravada,
                'isv_15'         => $isv15,
                'detalles'       => $comanda->detalles->map(fn($d) => [
                    'cantidad'         => $d->cantidad,
                    'platillo'         => $d->platillo?->nombre ?? 'Platillo',
                    'tamano'           => $d->tamano?->nombre ?? '',
                    'notas'            => $d->notas ?? '',
                    'precio'           => (float) ($d->precio_unitario ?? 0),
                    'descuento'        => (int) ($d->descuento ?? 0),
                    'monto_descuento'  => (float) ($d->monto_descuento ?? 0),
                    'subtotal'         => (float) $d->subtotal,
                ])->values()->toArray(),
            ]);

            $this->js("window.imprimirFactura($facturaData)");
        });

        $this->showPago = false;
        $this->comandaAPagar = null;
        $this->tipoPago = '';
    }

    public function cancelarPago(): void
    {
        $this->showPago = false;
        $this->comandaAPagar = null;
        $this->tipoPago = '';
        $this->paso = 'pago';
        $this->clienteNombre = '';
        $this->clienteRtn = '';
    }
};
?>

<div
    class="min-h-screen text-white"
    style="background: #080a0e; font-family: 'Courier New', monospace;"
    wire:poll.5s
>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
window.imprimirFactura = function(data) {
    var intentos = 0;
    function intentar() {
        if (typeof window.jspdf === 'undefined' || typeof window.jspdf.jsPDF === 'undefined') {
            if (++intentos < 20) setTimeout(intentar, 300);
            return;
        }
        construirPDF(data);
    }
    intentar();
};

function construirPDF(data) {
    var jsPDF = window.jspdf.jsPDF;

    var itemLines = 0;
    (data.detalles || []).forEach(function(d) {
        itemLines += 1;
        if (d.tamano)    itemLines += 0.75;
        if (d.descuento) itemLines += 0.75;
        if (d.notas)     itemLines += 0.75;
    });

    var altoPagina = 140 + (itemLines * 4.5) + 65;
    if (altoPagina < 200) altoPagina = 200;

    var hayDescuentos = (data.detalles || []).some(function(d) { return d.descuento > 0; });
    if (hayDescuentos) altoPagina += 6;

    var doc = new jsPDF({ unit: 'mm', format: [80, altoPagina], orientation: 'portrait' });

    var W     = 80;
    var y     = 6;
    var green = [0, 120, 60];
    var black = [20, 20, 20];
    var gray  = [110, 110, 110];

    function setGreen()  { doc.setTextColor(green[0], green[1], green[2]); }
    function setBlack()  { doc.setTextColor(black[0], black[1], black[2]); }
    function setGray()   { doc.setTextColor(gray[0],  gray[1],  gray[2]);  }
    function fillGreen() { doc.setFillColor(green[0], green[1], green[2]); }

    doc.setFont('helvetica', 'bold');
    doc.setFontSize(7.5);
    setGreen();
    doc.text('RESTAURANTE Y PIZZERIA', W / 2, y, { align: 'center' });
    y += 5;

    doc.setFontSize(11);
    doc.text('MI PEQUE\u00d1O JARDIN', W / 2, y, { align: 'center' });
    y += 5.5;

    doc.setFont('helvetica', 'normal');
    doc.setFontSize(6);
    setGray();
    var headerLines = [
        'Prop. Carmen Marisela Hernandez T.',
        'SUCURSAL: Residencial San Isidro, El Paraiso',
        'R.T.N. 0704198900271   Tel: 2793-6471 / 9359-6424',
        'E-mail: picolino5289@gmail.com',
    ];
    headerLines.forEach(function(l) {
        doc.text(l, W / 2, y, { align: 'center' });
        y += 4;
    });
    y += 1;

    fillGreen();
    doc.roundedRect(3, y, W - 6, 6, 1, 1, 'F');
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(5.8);
    doc.setTextColor(255, 255, 255);
    doc.text('CAI: 47E269-41C2F2-514DE0-63BE03-090945-33', W / 2, y + 3.8, { align: 'center' });
    y += 9;

    var meses = ['','enero','febrero','marzo','abril','mayo','junio',
                 'julio','agosto','septiembre','octubre','noviembre','diciembre'];
    var fp    = data.fecha.split(' ')[0].split('/');
    var dd = fp[0], mesIdx = parseInt(fp[1]), aaaa = fp[2];

    doc.setFont('helvetica', 'normal');
    doc.setFontSize(6.5);
    setBlack();
    doc.text('Fecha:  ' + dd + '  de  ' + meses[mesIdx] + '  del  ' + aaaa, W / 2, y, { align: 'center' });
    y += 7;

    function badge(label, x, yy, ancho) {
        fillGreen();
        doc.roundedRect(x, yy - 4, ancho, 5.5, 1, 1, 'F');
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(6.5);
        doc.setTextColor(255, 255, 255);
        doc.text(label, x + 2, yy);
    }

    badge('Cliente:', 3, y, 18);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(6.5);
    setBlack();
    doc.text(data.cliente || 'Consumidor Final', 23, y);
    y += 5;

    if (data.rtn) {
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(6.5);
        setGray();
        doc.text('R.T.N.: ' + data.rtn, 23, y);
        setBlack();
        y += 5;
    }

    doc.setFont('helvetica', 'normal');
    doc.setFontSize(6);
    setGray();
    doc.text('Mesa ' + data.mesa + '  \u00b7  ' + data.mesero, 23, y);
    setBlack();
    y += 7;

    badge('Tipo de pago:', 3, y, 26);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(6.5);
    setBlack();
    doc.text(data.tipo_pago.charAt(0).toUpperCase() + data.tipo_pago.slice(1), 31, y);
    y += 7;

    doc.setDrawColor(green[0], green[1], green[2]);
    doc.setLineWidth(0.4);
    doc.line(3, y, W - 3, y);
    y += 5;

    doc.setFont('helvetica', 'bold');
    doc.setFontSize(6.5);
    setGreen();
    doc.text('CANT.',        5,  y);
    doc.text('DESCRIPCI\u00d3N', 17, y);
    doc.text('P/U',         52, y, { align: 'right' });
    doc.text('TOTAL',       74, y, { align: 'right' });
    y += 2.5;
    doc.line(3, y, W - 3, y);
    y += 5;

    doc.setFont('helvetica', 'normal');
    doc.setFontSize(6.5);
    setBlack();

    var acum = 0;
    (data.detalles || []).forEach(function(d) {
        var nombre   = d.platillo.length > 22 ? d.platillo.substring(0, 22) + '...' : d.platillo;
        var precio   = parseFloat(d.precio)   || 0;
        var subtotal = parseFloat(d.subtotal) || 0;
        acum += subtotal;

        doc.setFont('helvetica', 'normal');
        doc.setFontSize(6.5);
        setBlack();
        doc.text(d.cantidad + 'x', 10, y, { align: 'right' });
        doc.text(nombre, 17, y);
        doc.text(precio > 0 ? precio.toFixed(2) : '-', 52, y, { align: 'right' });
        doc.text(subtotal.toFixed(2), 74, y, { align: 'right' });
        y += 5;

        if (d.tamano) {
            doc.setFontSize(5.5); setGray();
            doc.text('  Tama\u00f1o: ' + d.tamano, 17, y);
            y += 4;
        }
        if (d.descuento) {
            doc.setFontSize(5.5);
            doc.setTextColor(180, 40, 40);
            doc.text('  Descuento: ' + d.descuento + '% (-L ' + (parseFloat(d.monto_descuento) || 0).toFixed(2) + ')', 17, y);
            setBlack();
            y += 4;
        }
        if (d.notas) {
            doc.setFontSize(5.5); setGray();
            doc.text('  Nota: ' + d.notas, 17, y);
            y += 4;
        }
    });

    y += 2;
    doc.setDrawColor(green[0], green[1], green[2]);
    doc.setLineWidth(0.4);
    doc.line(3, y, W - 3, y);
    y += 5;

    var total       = parseFloat(data.total)       || acum;
    var baseGravada = parseFloat(data.base_gravada) || (total / 1.15);
    var isv15       = parseFloat(data.isv_15)       || (total - baseGravada);

    var totalDescuento = 0;
    (data.detalles || []).forEach(function(d) {
        totalDescuento += parseFloat(d.monto_descuento) || 0;
    });
    totalDescuento = Math.round(totalDescuento * 100) / 100;

    var totalRows = [
        { label: 'Importe Exonerado L.',   val: '0.00',                 bold: false, color: 'black' },
        { label: 'Importe Exento L.',       val: '0.00',                 bold: false, color: 'black' },
        { label: 'Importe Gravado 15% L.',  val: baseGravada.toFixed(2), bold: false, color: 'black' },
        { label: 'ISV 15% L.',              val: isv15.toFixed(2),       bold: false, color: 'black' },
    ];

    if (totalDescuento > 0) {
        totalRows.push({ label: 'Total Descuentos L.', val: '-' + totalDescuento.toFixed(2), bold: false, color: 'red' });
    }

    totalRows.push({ label: 'TOTAL A PAGAR L.', val: total.toFixed(2), bold: true, color: 'green' });

    totalRows.forEach(function(row) {
        doc.setFont('helvetica', row.bold ? 'bold' : 'normal');
        doc.setFontSize(row.bold ? 7.5 : 6.5);
        if (row.color === 'green')    { setGreen(); }
        else if (row.color === 'red') { doc.setTextColor(180, 40, 40); }
        else                          { setBlack(); }
        doc.text(row.label,  5,  y);
        doc.text(row.val,   74, y, { align: 'right' });
        y += row.bold ? 6 : 5;
    });

    y += 3;
    badge('FACTURA', 3, y, 18);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(6.5);
    setBlack();
    doc.text('Contado', 23, y);
    doc.rect(33, y - 3.5, 3.5, 3.5);
    doc.text('Cr\u00e9dito', 39, y);
    doc.rect(49, y - 3.5, 3.5, 3.5);

    if (data.tipo_pago === 'efectivo') {
        fillGreen(); doc.rect(33.4, y - 3.1, 2.7, 2.7, 'F');
    } else {
        fillGreen(); doc.rect(49.4, y - 3.1, 2.7, 2.7, 'F');
    }
    y += 6;

    doc.setFont('helvetica', 'normal');
    doc.setFontSize(6.5);
    setBlack();
    var numFactura = data.numero_factura || ('002-001-01-' + String(data.factura_id || data.comanda_id).padStart(8, '0'));
    doc.text(numFactura, 5, y);
    y += 9;

    doc.setDrawColor(green[0], green[1], green[2]);
    doc.setLineWidth(0.3);
    doc.line(3, y, W - 3, y);
    y += 4;

    doc.setFont('helvetica', 'normal');
    doc.setFontSize(5.5);
    setGray();
    doc.text('Rango Autorizado: 002-001-01-00003001 / 002-001-01-00003600', W / 2, y, { align: 'center' });
    y += 4;
    doc.text('Fecha l\u00edmite de Emisi\u00f3n: 08/01/2027', W / 2, y, { align: 'center' });
    y += 6;

    doc.setFont('helvetica', 'bold');
    doc.setFontSize(6);
    setGreen();
    doc.text('LA FACTURA ES BENEFICIO DE TODOS "EX\u00cdJALA"', W / 2, y, { align: 'center' });
    y += 6;
    doc.setFont('helvetica', 'bolditalic');
    doc.setFontSize(9);
    doc.text('Gracias Por Su Preferencia', W / 2, y, { align: 'center' });

    doc.save('factura-' + (data.numero_factura || data.factura_id) + '.pdf');
}
</script>

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
                                    <div class="flex items-center justify-between mt-0.5">
                                        <div class="text-xs" style="color: #475569;">
                                            {{ $detalle->cantidad }}× L {{ number_format($detalle->precio_unitario, 2) }}
                                        </div>
                                        <div class="text-xs font-bold" style="color: #94a3b8;">
                                            L {{ number_format($detalle->subtotal, 2) }}
                                        </div>
                                    </div>
                                    @if($detalle->descuento > 0)
                                        <div class="text-xs font-bold mt-0.5" style="color: #f87171;">
                                            🏷️ −{{ $detalle->descuento }}% (−L {{ number_format($detalle->monto_descuento, 2) }})
                                        </div>
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
                            wire:click="abrirModalPago({{ $comanda->id }})"
                            class="text-xs px-3 py-1.5 rounded-xl font-bold hover:opacity-80 transition-all"
                            style="background: #eab30822; border: 1px solid #eab30844; color: #eab308;"
                        >
                            💳 Pagar
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
        @endif
    </main>

    {{-- ── MODAL DE PAGO ────────────────────────────────────────── --}}
    @if($showPago)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0" style="background: rgba(0,0,0,0.82);"
             wire:click="cancelarPago"></div>

        <div class="relative w-full max-w-sm rounded-2xl p-6 flex flex-col gap-5"
             style="background: #0d1117; border: 1.5px solid #eab30844; box-shadow: 0 0 60px #eab30811, 0 24px 48px rgba(0,0,0,0.5);">

            {{-- PASO 1: Tipo de pago --}}
            @if($paso === 'pago')

                <div class="flex items-center justify-center">
                    <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-3xl"
                         style="background: #eab30822; border: 1.5px solid #eab30844;">
                        💳
                    </div>
                </div>

                <div class="text-center">
                    <div class="font-black text-lg" style="color: #f1f5f9;">Tipo de pago</div>
                    <div class="text-sm mt-1" style="color: #475569;">Elige cómo se realizará el cobro</div>
                </div>

                <div class="flex flex-col gap-3">
                    @foreach([
                        ['value' => 'efectivo',      'label' => 'Efectivo',      'icon' => '💵', 'color' => '#22c55e'],
                        ['value' => 'transferencia', 'label' => 'Transferencia', 'icon' => '🏦', 'color' => '#3b82f6'],
                        ['value' => 'tarjeta',       'label' => 'Tarjeta',       'icon' => '💳', 'color' => '#a855f7'],
                    ] as $opcion)
                        <button
                            type="button"
                            wire:click="$set('tipoPago', '{{ $opcion['value'] }}')"
                            class="flex items-center gap-4 px-4 py-3 rounded-xl font-bold text-sm transition-all"
                            style="
                                background: {{ $tipoPago === $opcion['value'] ? $opcion['color'].'33' : '#1e2530' }};
                                border: 1.5px solid {{ $tipoPago === $opcion['value'] ? $opcion['color'].'88' : '#2a3441' }};
                                color: {{ $tipoPago === $opcion['value'] ? $opcion['color'] : '#94a3b8' }};
                            "
                        >
                            <span class="text-xl">{{ $opcion['icon'] }}</span>
                            <span>{{ $opcion['label'] }}</span>
                            @if($tipoPago === $opcion['value'])
                                <span class="ml-auto">✓</span>
                            @endif
                        </button>
                    @endforeach
                </div>

                <div class="flex gap-3 mt-1">
                    <button
                        type="button"
                        wire:click="cancelarPago"
                        class="flex-1 py-3 rounded-xl font-bold text-sm hover:opacity-80 transition-all"
                        style="background: #1e2530; color: #94a3b8; border: 1.5px solid #2a3441;"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        wire:click="avanzarACliente"
                        @if(!$tipoPago) disabled @endif
                        class="flex-1 py-3 rounded-xl font-bold text-sm transition-all"
                        style="
                            background: {{ $tipoPago ? '#eab30822' : '#1e2530' }};
                            color: {{ $tipoPago ? '#eab308' : '#475569' }};
                            border: 1.5px solid {{ $tipoPago ? '#eab30844' : '#2a3441' }};
                            {{ !$tipoPago ? 'opacity:.45; cursor:not-allowed;' : '' }}
                        "
                    >
                        Siguiente →
                    </button>
                </div>

            {{-- PASO 2: Datos del cliente --}}
            @elseif($paso === 'cliente')

                <div class="flex items-center justify-center">
                    <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-3xl"
                         style="background: #10b98122; border: 1.5px solid #10b98144;">
                        👤
                    </div>
                </div>

                <div class="text-center">
                    <div class="font-black text-lg" style="color: #f1f5f9;">Datos del cliente</div>
                    <div class="text-sm mt-1" style="color: #475569;">El nombre es obligatorio para la factura</div>
                </div>

                <div class="flex flex-col gap-3">
                    <div>
                        <label class="block text-xs font-bold mb-1.5" style="color:#94a3b8;">
                            Nombre <span style="color:#f87171;">*</span>
                        </label>
                        <input
                            type="text"
                            wire:model.live="clienteNombre"
                            placeholder="Nombre del cliente"
                            class="w-full px-4 py-3 rounded-xl text-sm outline-none transition-all"
                            style="background:#1e2530; border:1.5px solid {{ $errors->has('clienteNombre') ? '#ef4444' : '#2a3441' }};
                                   color:#f1f5f9;"
                            autofocus
                        />
                        @error('clienteNombre')
                            <div class="text-xs mt-1.5 font-semibold" style="color:#f87171;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold mb-1.5" style="color:#94a3b8;">
                            RTN <span style="color:#475569;">(opcional)</span>
                        </label>
                        <input
                            type="text"
                            wire:model.live="clienteRtn"
                            placeholder="0000-0000-000000"
                            class="w-full px-4 py-3 rounded-xl text-sm outline-none transition-all"
                            style="background:#1e2530; border:1.5px solid #2a3441; color:#f1f5f9;"
                        />
                    </div>
                </div>

                <div class="flex gap-3 mt-1">
                    <button
                        type="button"
                        wire:click="volverAPago"
                        class="flex-1 py-3 rounded-xl font-bold text-sm hover:opacity-80 transition-all"
                        style="background: #1e2530; color: #94a3b8; border: 1.5px solid #2a3441;"
                    >
                        ← Volver
                    </button>
                    <button
                        type="button"
                        wire:click="realizarPago"
                        class="flex-1 py-3 rounded-xl font-bold text-sm transition-all"
                        style="background:#10b98122; color:#10b981; border:1.5px solid #10b98144;"
                    >
                        💰 Confirmar
                    </button>
                </div>

            @endif
            {{-- fin @if($paso) --}}

        </div>
    </div>
    @endif
    {{-- fin @if($showPago) --}}

    {{-- ── MODAL: CAJA CERRADA ─────────────────────────────────── --}}
    @if($showCajaCerrada)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0" style="background: rgba(0,0,0,0.82);"
             wire:click="$set('showCajaCerrada', false)"></div>

        <div class="relative w-full max-w-sm rounded-2xl p-7 flex flex-col gap-5 text-center"
             style="background: #0d1117; border: 1.5px solid #ef444444;
                    box-shadow: 0 0 60px #ef444415, 0 24px 48px rgba(0,0,0,0.6);">

            <div class="flex justify-center">
                <div class="w-20 h-20 rounded-2xl flex items-center justify-center text-4xl"
                     style="background: #ef444422; border: 1.5px solid #ef444444;">
                    🔒
                </div>
            </div>

            <div>
                <div class="font-black text-xl mb-2" style="color: #f1f5f9;">Caja cerrada</div>
                <div class="text-sm leading-relaxed" style="color: #64748b;">
                    No es posible realizar pagos ni emitir facturas mientras la caja esté cerrada.
                    <br><br>
                    Abre la caja para comenzar a facturar.
                </div>
            </div>

            <div class="flex flex-col gap-3">
                <a
                    href="{{ route('caja') }}"
                    class="w-full py-3.5 rounded-xl font-black text-sm transition-all text-center"
                    style="background:#10b98122; color:#10b981; border:1.5px solid #10b98144;"
                >
                    🔓 Ir a apertura de caja
                </a>
                <button
                    type="button"
                    wire:click="$set('showCajaCerrada', false)"
                    class="w-full py-3 rounded-xl font-bold text-sm transition-all hover:opacity-80"
                    style="background: #1a2030; color: #94a3b8; border: 1.5px solid #2a3441;"
                >
                    Cerrar
                </button>
            </div>
        </div>
    </div>
    @endif
    {{-- fin @if($showCajaCerrada) --}}

</div>
