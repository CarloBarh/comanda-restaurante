<?php

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use App\Models\Mesa;
use App\Models\Categoria;
use App\Models\Platillo;
use App\Models\Comanda;
use App\Models\ComandaDetalle;

new class extends Component
{
    public bool $showTamanoModal = false;
    public ?int $tamanoPlatilloId = null;
    public string $tamanoPlatilloNombre = '';
    public array $tamanosOpciones = []; // [{tamano_id, tamano_nombre, precio}]
    public int $mesaId;
    public ?int $categoriaId = null;
    public string $busqueda = '';
    public bool $showNotaModal = false;
    public ?string $notaKey = null;
    public string $notaTexto = '';
    public string $notaTitulo = '';
    public ?int $comandaId = null; // si la mesa ya tiene una comanda en_proceso
    public ?int $subcategoriaId = null;

    // ── Modal de acción (Nota | Descuento) ──────────────────────────────────
    public bool $showAccionModal = false;
    public ?string $accionKey = null;
    public string $accionNombre = '';

    // ── Modal de descuento ───────────────────────────────────────────────────
    public bool $showDescuentoModal = false;
    public ?string $descuentoKey = null;
    public string $descuentoTexto = '';   // string para el input; se valida como entero
    public string $descuentoNombre = '';

    public function mount(int $mesa): void
    {
        $this->mesaId = $mesa;

        $this->comandaId = \App\Models\Comanda::where('mesa_id', $this->mesaId)
            ->where('estado', 'en_proceso')
            ->latest('id')
            ->value('id');

        $first = \App\Models\Categoria::query()->orderBy('nombre')->first();
        $this->categoriaId = $first?->id;

        $this->subcategoriaId = null;
    }

    protected function draftKey(): string
    {
        if ($this->comandaId) {
            return "draft_comanda_{$this->comandaId}";
        }
        return "draft_mesa_{$this->mesaId}";
    }

    public function with(): array
    {
        // 🔄 Sincronizar comanda activa en cada render
        $activeId = \App\Models\Comanda::where('mesa_id', $this->mesaId)
            ->where('estado', 'en_proceso')
            ->latest('id')
            ->value('id');

        if ($this->comandaId && !$activeId) {
            session()->forget("draft_comanda_{$this->comandaId}");
            session()->forget("draft_mesa_{$this->mesaId}");
            $this->comandaId = null;

            $this->showNotaModal = false;
            $this->notaKey = null;
            $this->notaTexto = '';
            $this->notaTitulo = '';

            $this->showTamanoModal = false;
            $this->tamanoPlatilloId = null;
            $this->tamanoPlatilloNombre = '';
            $this->tamanosOpciones = [];

            $this->showAccionModal = false;
            $this->accionKey = null;
            $this->accionNombre = '';

            $this->showDescuentoModal = false;
            $this->descuentoKey = null;
            $this->descuentoTexto = '';
            $this->descuentoNombre = '';
        } else {
            $this->comandaId = $activeId;
        }

        $mesa = Mesa::findOrFail($this->mesaId);

        $enviados = collect();
        $totalEnviado = 0.0;

        if ($this->comandaId) {
            $enviados = \App\Models\ComandaDetalle::query()
                ->with(['platillo', 'tamano'])
                ->where('comanda_id', $this->comandaId)
                ->orderBy('id')
                ->get()
                ->map(function ($d) {
                    return [
                        'nombre' => $d->platillo?->nombre ?? 'Platillo',
                        'tamano' => $d->tamano?->nombre,
                        'cantidad' => (int) $d->cantidad,
                        'precio_unitario' => (float) $d->precio_unitario,
                        'subtotal' => (float) $d->subtotal,
                        'notas' => $d->notas,
                    ];
                });

            $totalEnviado = (float) $enviados->sum('subtotal');
        }

        $categorias = Categoria::query()->orderBy('nombre')->get();

        $subcategorias = \App\Models\Subcategoria::query()
            ->where('categoria_id', $this->categoriaId)
            ->orderBy('nombre')
            ->get();

        $platillos = Platillo::query()
            ->when($this->categoriaId, fn($q) => $q->where('categoria_id', $this->categoriaId))
            ->when($this->subcategoriaId, fn($q) => $q->where('subcategoria_id', $this->subcategoriaId))
            ->when($this->busqueda !== '', fn($q) => $q->where('nombre', 'like', '%'.$this->busqueda.'%'))
            ->orderBy('nombre')
            ->get();

        $draft = session()->get($this->draftKey(), []);

        $items = collect($draft)->map(function ($item, $key) {
            $p = Platillo::find($item['platillo_id']);

            $cantidad = (int) ($item['cantidad'] ?? 1);
            $tamanoId = $item['tamano_id'] ?? null;

            if ($tamanoId) {
                $precioUnit = (float) (\App\Models\PlatilloPrecio::where('platillo_id', $item['platillo_id'])
                    ->where('tamano_id', $tamanoId)
                    ->value('precio') ?? 0);
            } else {
                $precioUnit = (float) ($p?->precio ?? 0);
            }

            $descuento = (int) ($item['descuento'] ?? 0); // % entero 0-100
            $subtotalBruto = $precioUnit * $cantidad;
            $subtotal = $descuento > 0
                ? round($subtotalBruto * (1 - $descuento / 100), 2)
                : $subtotalBruto;

            $tamanoNombre = null;
            if ($tamanoId) {
                $tamanoNombre = \App\Models\Tamano::whereKey($tamanoId)->value('nombre');
            }

            return [
                'key' => $key,
                'nombre' => $p?->nombre ?? 'Platillo',
                'tamano' => $tamanoNombre,
                'cantidad' => $cantidad,
                'precio_unitario' => $precioUnit,
                'descuento' => $descuento,
                'subtotal' => $subtotal,
                'notas' => $item['notas'] ?? null,
            ];
        })->values();

        $totalDraft = (float) $items->sum('subtotal');
        $totalGeneral = (float) ($totalEnviado + $totalDraft);

        return compact(
            'mesa',
            'categorias',
            'subcategorias',
            'platillos',
            'enviados',
            'totalEnviado',
            'items',
            'totalDraft',
            'totalGeneral'
        );
    }

    public function seleccionarCategoria(int $categoriaId): void
    {
        $this->categoriaId = $categoriaId;
        $this->busqueda = '';
        $this->subcategoriaId = null;
    }

    public function seleccionarSubcategoria(?int $subcategoriaId): void
    {
        $this->subcategoriaId = $subcategoriaId;
    }

    public function clickPlatillo(int $platilloId): void
    {
        $platillo = \App\Models\Platillo::findOrFail($platilloId);

        $tamanos = \App\Models\PlatilloPrecio::query()
            ->join('tamanos', 'tamanos.id', '=', 'platillo_precios.tamano_id')
            ->where('platillo_precios.platillo_id', $platilloId)
            ->orderBy('tamanos.id')
            ->get([
                'platillo_precios.tamano_id as tamano_id',
                'tamanos.nombre as tamano_nombre',
                'platillo_precios.precio as precio',
            ])
            ->toArray();

        if (count($tamanos) > 0) {
            $this->showTamanoModal = true;
            $this->tamanoPlatilloId = $platilloId;
            $this->tamanoPlatilloNombre = $platillo->nombre;
            $this->tamanosOpciones = $tamanos;
            return;
        }

        $this->agregarAlDraft($platilloId, null);
    }

    public function agregarAlDraft(int $platilloId, ?int $tamanoId): void
    {
        $draft = session()->get($this->draftKey(), []);

        $key = $platilloId . '|' . ($tamanoId ?? '');

        if (!isset($draft[$key])) {
            $draft[$key] = [
                'platillo_id' => $platilloId,
                'tamano_id' => $tamanoId,
                'cantidad' => 1,
                'notas' => null,
                'descuento' => 0,
            ];
        } else {
            $draft[$key]['cantidad']++;
        }

        session()->put($this->draftKey(), $draft);
    }

    public function seleccionarTamano(int $tamanoId): void
    {
        if (!$this->tamanoPlatilloId) return;

        $this->agregarAlDraft($this->tamanoPlatilloId, $tamanoId);
        $this->cerrarTamanoModal();
    }

    public function cerrarTamanoModal(): void
    {
        $this->showTamanoModal = false;
        $this->tamanoPlatilloId = null;
        $this->tamanoPlatilloNombre = '';
        $this->tamanosOpciones = [];
    }

    // ── Modal de acción ──────────────────────────────────────────────────────

    /**
     * Se llama al tocar un item del borrador.
     * Abre el modal para elegir entre Nota y Descuento.
     */
    public function abrirAccionItem(string $key): void
    {
        $draft = session()->get($this->draftKey(), []);

        if (!isset($draft[$key])) return;

        $platilloId = $draft[$key]['platillo_id'] ?? null;
        $platillo = $platilloId ? \App\Models\Platillo::find($platilloId) : null;

        $this->accionKey = $key;
        $this->accionNombre = $platillo?->nombre ?? 'Platillo';
        $this->showAccionModal = true;
    }

    public function cerrarAccionModal(): void
    {
        $this->showAccionModal = false;
        $this->accionKey = null;
        $this->accionNombre = '';
    }

    /**
     * Desde el modal de acción, el usuario elige "Nota".
     */
    public function irANota(): void
    {
        $key = $this->accionKey;
        $this->cerrarAccionModal();

        if ($key) {
            $this->abrirNotas($key);
        }
    }

    /**
     * Desde el modal de acción, el usuario elige "Descuento".
     */
    public function irADescuento(): void
    {
        $key = $this->accionKey;
        $this->cerrarAccionModal();

        if ($key) {
            $this->abrirDescuento($key);
        }
    }

    // ── Modal de descuento ───────────────────────────────────────────────────

    public function abrirDescuento(string $key): void
    {
        $draft = session()->get($this->draftKey(), []);

        if (!isset($draft[$key])) return;

        $platilloId = $draft[$key]['platillo_id'] ?? null;
        $platillo = $platilloId ? \App\Models\Platillo::find($platilloId) : null;

        $this->descuentoKey = $key;
        $this->descuentoNombre = $platillo?->nombre ?? 'Platillo';
        $descuentoActual = (int) ($draft[$key]['descuento'] ?? 0);
        $this->descuentoTexto = $descuentoActual > 0 ? (string) $descuentoActual : '';
        $this->showDescuentoModal = true;
    }

    public function guardarDescuento(): void
    {
        if (!$this->descuentoKey) return;

        // Validar: solo enteros 0-100
        $valor = trim($this->descuentoTexto);

        if ($valor === '' || !ctype_digit($valor)) {
            $valor = '0';
        }

        $porcentaje = (int) $valor;
        $porcentaje = max(0, min(100, $porcentaje));

        $draft = session()->get($this->draftKey(), []);

        if (!isset($draft[$this->descuentoKey])) return;

        $draft[$this->descuentoKey]['descuento'] = $porcentaje;

        session()->put($this->draftKey(), $draft);

        $this->cerrarDescuento();
    }

    public function quitarDescuento(): void
    {
        if (!$this->descuentoKey) return;

        $draft = session()->get($this->draftKey(), []);

        if (isset($draft[$this->descuentoKey])) {
            $draft[$this->descuentoKey]['descuento'] = 0;
            session()->put($this->draftKey(), $draft);
        }

        $this->descuentoTexto = '';
        $this->cerrarDescuento();
    }

    public function cerrarDescuento(): void
    {
        $this->showDescuentoModal = false;
        $this->descuentoKey = null;
        $this->descuentoTexto = '';
        $this->descuentoNombre = '';
    }

    // ── Volver / Enviar ──────────────────────────────────────────────────────

    public function volverAMesas()
    {
        session()->forget($this->draftKey());

        if (!$this->comandaId) {
            \App\Models\Mesa::whereKey($this->mesaId)->update(['estado' => 'libre']);
        }

        return redirect()->route('mesas');
    }

    public function enviarACocina()
    {
        $draftKey = $this->draftKey();
        $draft = session()->get($draftKey, []);

        if (count($draft) === 0) {
            $this->addError('draft', 'Agrega al menos un platillo antes de enviar.');
            return null;
        }

        $meseroId = session('mesero_id');
        if (!$meseroId) {
            return redirect()->route('pin');
        }

        DB::transaction(function () use ($draft, $meseroId) {

            if ($this->comandaId) {
                $comanda = \App\Models\Comanda::findOrFail($this->comandaId);
            } else {
                $comanda = \App\Models\Comanda::create([
                    'mesa_id' => $this->mesaId,
                    'mesero_id' => $meseroId,
                    'estado' => 'en_proceso',
                    'total' => 0,
                ]);

                \App\Models\Mesa::whereKey($this->mesaId)->update(['estado' => 'ocupada']);
                $this->comandaId = $comanda->id;
            }

            foreach ($draft as $item) {
                $platillo = \App\Models\Platillo::findOrFail($item['platillo_id']);

                $tamanoId = $item['tamano_id'] ?? null;
                if ($tamanoId === '' || $tamanoId === 0 || $tamanoId === '0') {
                    $tamanoId = null;
                } elseif ($tamanoId !== null) {
                    $tamanoId = (int) $tamanoId;
                }

                if ($tamanoId) {
                    $precioUnit = (float) (\App\Models\PlatilloPrecio::where('platillo_id', $platillo->id)
                        ->where('tamano_id', $tamanoId)
                        ->value('precio') ?? 0);
                } else {
                    $precioUnit = (float) ($platillo->precio ?? 0);
                }

                $cantidad = (int) ($item['cantidad'] ?? 1);
                $descuento = (int) ($item['descuento'] ?? 0);
                $subtotalBruto = $precioUnit * $cantidad;
                $subtotal = $descuento > 0
                    ? round($subtotalBruto * (1 - $descuento / 100), 2)
                    : $subtotalBruto;
                $montoDescuento = round($subtotalBruto - $subtotal, 2);

                \App\Models\ComandaDetalle::create([
                    'comanda_id' => $comanda->id,
                    'platillo_id' => $platillo->id,
                    'tamano_id' => $tamanoId,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precioUnit,
                    'subtotal' => $subtotal,
                    'estado' => 'pendiente',
                    'notas' => $item['notas'] ?? null,
                    'descuento' => $descuento,
                    'monto_descuento' => $montoDescuento,
                ]);
            }

            $nuevoTotal = (float) \App\Models\ComandaDetalle::where('comanda_id', $comanda->id)->sum('subtotal');
            $comanda->update(['total' => $nuevoTotal]);
        });

        session()->forget($draftKey);   // ✅ ahora sí borra el correcto
        session()->forget('mesero_id');

        return redirect()->route('pin');
    }

    public function imagenUrl(?string $imagen): string
    {
        if (! $imagen) return 'https://via.placeholder.com/400x300?text=Sin+Imagen';
        if (str_starts_with($imagen, 'http')) return $imagen;
        return asset('storage/'.$imagen);
    }

    public function quitar(string $key): void
    {
        $draft = session()->get($this->draftKey(), []);

        if (!isset($draft[$key])) return;

        $draft[$key]['cantidad'] = ((int) $draft[$key]['cantidad']) - 1;

        if ($draft[$key]['cantidad'] <= 0) {
            unset($draft[$key]);
        }

        session()->put($this->draftKey(), $draft);
    }

    public function eliminarItem(string $key): void
    {
        $draft = session()->get($this->draftKey(), []);

        if (isset($draft[$key])) {
            unset($draft[$key]);
            session()->put($this->draftKey(), $draft);
        }
    }

    public function abrirNotas(string $key): void
    {
        $draft = session()->get($this->draftKey(), []);

        if (!isset($draft[$key])) return;

        $platilloId = $draft[$key]['platillo_id'] ?? null;
        $platillo = $platilloId ? \App\Models\Platillo::find($platilloId) : null;

        $this->notaKey = $key;
        $this->notaTexto = (string) ($draft[$key]['notas'] ?? '');
        $this->notaTitulo = $platillo?->nombre ?? 'Notas';
        $this->showNotaModal = true;
    }

    public function guardarNotas(): void
    {
        if (!$this->notaKey) return;

        $draft = session()->get($this->draftKey(), []);

        if (!isset($draft[$this->notaKey])) return;

        $texto = trim($this->notaTexto);
        $draft[$this->notaKey]['notas'] = $texto === '' ? null : $texto;

        session()->put($this->draftKey(), $draft);
        $this->cerrarNotas();
    }

    public function limpiarNotas(): void
    {
        if (!$this->notaKey) return;

        $draft = session()->get($this->draftKey(), []);
        if (isset($draft[$this->notaKey])) {
            $draft[$this->notaKey]['notas'] = null;
            session()->put($this->draftKey(), $draft);
        }

        $this->notaTexto = '';
        $this->cerrarNotas();
    }

    public function cerrarNotas(): void
    {
        $this->showNotaModal = false;
        $this->notaKey = null;
        $this->notaTexto = '';
        $this->notaTitulo = '';
    }
};
?>

<div class="min-h-screen bg-slate-950 text-white" wire:poll.visible.3s>
    <div class="h-screen flex">

        {{-- Sidebar categorías --}}
        <aside class="w-64 md:w-72 bg-slate-900/60 border-r border-slate-800 p-4 flex flex-col">
            <div class="mb-4">
                @if($comandaId)
                    <div class="text-xs mt-2 inline-flex items-center gap-2 px-2 py-1 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-200">
                        Mesa ocupada — agregando a comanda #{{ $comandaId }}
                    </div>
                @else
                    <div class="text-xs mt-2 inline-flex items-center gap-2 px-2 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-200">
                        Mesa disponible — borrador
                    </div>
                @endif
                <div class="text-xs text-slate-400">Mesa</div>
                <div class="text-2xl font-black">{{ $mesa->zona }} - Mesa {{ $mesa->numero_zona }}</div>
            </div>

            <div class="mb-3">
                <input
                    type="text"
                    wire:model.live="busqueda"
                    placeholder="Buscar platillo..."
                    class="w-full rounded-xl bg-slate-950/60 border border-slate-800 px-3 py-2 outline-none focus:ring-2 focus:ring-slate-600"
                />
            </div>

            <div class="text-xs text-slate-400 mb-2">Categorías</div>

            <div class="flex-1 overflow-auto space-y-2 pr-1">
                @foreach($categorias as $cat)
                    <button
                        type="button"
                        wire:click="seleccionarCategoria({{ $cat->id }})"
                        class="
                            w-full text-left px-3 py-3 rounded-xl border transition
                            {{ $categoriaId === $cat->id
                                ? 'bg-slate-800 border-slate-700'
                                : 'bg-slate-950/30 border-slate-800 hover:bg-slate-900/50'
                            }}
                        "
                    >
                        <div class="font-semibold">{{ $cat->nombre }}</div>
                    </button>
                @endforeach
            </div>

            <div class="pt-4 border-t border-slate-800 mt-4">
                @error('draft')
                    <div class="text-red-400 text-sm mb-2">{{ $message }}</div>
                @enderror

                <button
                    type="button"
                    wire:click="enviarACocina"
                    class="w-full rounded-xl bg-emerald-600/90 hover:bg-emerald-600 px-4 py-3 font-bold transition"
                >
                    Enviar a cocina
                </button>

                <button
                    type="button"
                    wire:click="volverAMesas"
                    class="w-full mt-2 rounded-xl bg-slate-800 hover:bg-slate-700 px-4 py-3 font-semibold transition"
                >
                    Volver a mesas
                </button>
            </div>
        </aside>

        {{-- Grid platillos --}}
        <main class="flex-1 p-4 md:p-6 overflow-auto">
            <div class="flex items-end justify-between gap-4 mb-4">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold">
                        {{ optional($categorias->firstWhere('id', $categoriaId))->nombre ?? 'Platillos' }}
                    </h1>
                    <p class="text-slate-400 mt-1">Toca un platillo para agregarlo a la orden</p>
                </div>
            </div>

            @if($subcategorias->isNotEmpty())
                <div class="mb-4 flex flex-wrap gap-2">
                    <button
                        type="button"
                        wire:click="seleccionarSubcategoria(null)"
                        class="px-4 py-2 rounded-xl border text-sm font-semibold transition
                            {{ $subcategoriaId === null
                                ? 'bg-slate-800 border-slate-700 text-white'
                                : 'bg-slate-950/30 border-slate-800 text-slate-300 hover:bg-slate-900/50'
                            }}"
                    >
                        Todos
                    </button>

                    @foreach($subcategorias as $sub)
                        <button
                            type="button"
                            wire:click="seleccionarSubcategoria({{ $sub->id }})"
                            class="px-4 py-2 rounded-xl border text-sm font-semibold transition
                                {{ $subcategoriaId === $sub->id
                                    ? 'bg-slate-800 border-slate-700 text-white'
                                    : 'bg-slate-950/30 border-slate-800 text-slate-300 hover:bg-slate-900/50'
                                }}"
                        >
                            {{ $sub->nombre }}
                        </button>
                    @endforeach
                </div>
            @endif

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                @foreach($platillos as $p)
                    <button
                        type="button"
                        wire:click="clickPlatillo({{ $p->id }})"
                        class="rounded-2xl overflow-hidden border border-slate-800 bg-slate-900/40 hover:bg-slate-900/60 transition active:scale-[0.99]"
                    >
                        <div class="aspect-[4/3] bg-slate-950">
                            <img
                                src="{{ $this->imagenUrl($p->imagen) }}"
                                alt="{{ $p->nombre }}"
                                class="w-full h-full object-cover"
                                loading="lazy"
                            />
                        </div>
                        <div class="p-3">
                            <div class="font-bold leading-tight line-clamp-2">{{ $p->nombre }}</div>
                        </div>
                    </button>
                @endforeach
            </div>
        </main>

        {{-- Panel derecho: resumen --}}
        <aside class="hidden xl:block w-72 bg-slate-900/40 border-l border-slate-800 p-4 overflow-auto">
            <div class="font-bold text-lg mb-3">Orden</div>

            {{-- 1) YA ENVIADO (solo lectura) --}}
            @if($enviados->isNotEmpty())
                <div class="mb-4">
                    <div class="text-xs text-slate-400 mb-2">Ya enviado</div>

                    <div class="space-y-2">
                        @foreach($enviados as $it)
                            <div class="bg-slate-950/30 border border-slate-800 rounded-xl p-3">
                                <div class="font-semibold text-sm truncate">{{ $it['nombre'] }}</div>

                                @if(!empty($it['tamano']))
                                    <div class="text-xs text-slate-400 mt-0.5">Tamaño: {{ $it['tamano'] }}</div>
                                @endif

                                @if(!empty($it['notas']))
                                    <div class="text-xs text-slate-400 mt-1 line-clamp-2">
                                        📝 {{ $it['notas'] }}
                                    </div>
                                @endif

                                <div class="mt-2 flex items-center justify-between">
                                    <div class="text-slate-300 text-sm">x{{ $it['cantidad'] }}</div>
                                    <div class="font-black">L {{ number_format($it['subtotal'], 2) }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-3 flex items-center justify-between text-sm">
                        <div class="text-slate-400">Subtotal enviado</div>
                        <div class="font-black">L {{ number_format($totalEnviado, 2) }}</div>
                    </div>
                </div>
            @endif

            {{-- 2) NUEVO (BORRADOR) (editable) --}}
            <div class="mb-4">
                <div class="text-xs text-slate-400 mb-2">Nuevo (borrador)</div>

                @if($items->isEmpty())
                    <div class="text-slate-400 text-sm">Aún no agregas platillos nuevos.</div>
                @else
                    <div class="space-y-2">
                        @foreach($items as $it)
                            <div class="flex items-center justify-between gap-3 bg-slate-950/40 border border-slate-800 rounded-xl p-3">
                                {{-- Clickeable: abre modal de acción --}}
                                <button
                                    type="button"
                                    wire:click="abrirAccionItem('{{ $it['key'] }}')"
                                    class="min-w-0 flex-1 text-left"
                                >
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <div class="font-semibold text-sm truncate">{{ $it['nombre'] }}</div>

                                        @if(!empty($it['notas']))
                                            <span class="text-[11px] px-2 py-1 rounded-full bg-slate-800 border border-slate-700 text-slate-200">
                                                📝 Nota
                                            </span>
                                        @endif

                                        @if($it['descuento'] > 0)
                                            <span class="text-[11px] px-2 py-1 rounded-full bg-rose-900/60 border border-rose-700/50 text-rose-300 font-bold">
                                                −{{ $it['descuento'] }}%
                                            </span>
                                        @endif
                                    </div>

                                    @if(!empty($it['tamano']))
                                        <div class="text-xs text-slate-400 mt-0.5">Tamaño: {{ $it['tamano'] }}</div>
                                    @endif

                                    <div class="mt-1 flex items-center justify-between gap-2">
                                        <div class="text-slate-300 text-sm">
                                            L {{ number_format($it['precio_unitario'], 2) }} c/u
                                        </div>
                                        <div class="font-black text-slate-100">
                                            L {{ number_format($it['subtotal'], 2) }}
                                        </div>
                                    </div>

                                    @if(!empty($it['notas']))
                                        <div class="text-xs text-slate-400 mt-1 line-clamp-1">
                                            {{ $it['notas'] }}
                                        </div>
                                    @endif
                                </button>

                                <div class="flex items-center gap-2">
                                    <div class="w-14 h-12 rounded-xl bg-slate-800 border border-slate-700
                                                flex items-center justify-center font-black text-2xl">
                                        {{ $it['cantidad'] }}
                                    </div>

                                    <button
                                        type="button"
                                        wire:click="quitar('{{ $it['key'] }}')"
                                        class="w-12 h-12 rounded-xl bg-rose-600/90 hover:bg-rose-600 active:scale-[0.98] transition font-black text-2xl"
                                        title="Quitar (resta 1 o elimina)"
                                    >
                                        ✕
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- 3) TOTAL GENERAL --}}
            <div class="pt-4 border-t border-slate-800">
                <div class="flex items-center justify-between">
                    <div class="text-slate-300 font-semibold">Total</div>
                    <div class="text-2xl font-black">L {{ number_format($totalGeneral, 2) }}</div>
                </div>
            </div>

            @if(!$items->isEmpty())
                <div class="mt-4 pt-4 border-t border-slate-800">
                    <div class="flex items-center justify-between">
                        <div class="text-slate-300 font-semibold">Subtotal:</div>
                        <div class="text-2xl font-black">L {{ number_format($totalDraft, 2) }}</div>
                    </div>
                </div>
            @endif
        </aside>

    </div>

    {{-- ══════════════════════════════════════════════════════
         MODAL: Elegir acción (Nota | Descuento)
    ══════════════════════════════════════════════════════ --}}
    @if($showAccionModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/70" wire:click="cerrarAccionModal"></div>

            <div class="relative w-full max-w-sm rounded-2xl bg-slate-900 border border-slate-800 shadow-2xl p-5">
                <div class="flex items-start justify-between gap-4 mb-5">
                    <div>
                        <div class="text-sm text-slate-400">¿Qué deseas hacer con</div>
                        <div class="text-xl font-black leading-tight">{{ $accionNombre }}</div>
                    </div>

                    <button
                        type="button"
                        wire:click="cerrarAccionModal"
                        class="w-10 h-10 rounded-xl bg-slate-800 hover:bg-slate-700 font-black text-xl flex items-center justify-center flex-shrink-0"
                    >
                        ✕
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    {{-- Botón: Nota --}}
                    <button
                        type="button"
                        wire:click="irANota"
                        class="flex flex-col items-center justify-center gap-2 rounded-2xl border border-slate-700 bg-slate-800/60 hover:bg-slate-800 active:scale-[0.98] transition p-5"
                    >
                        <span class="text-3xl">📝</span>
                        <span class="font-bold text-base">Nota</span>
                        <span class="text-xs text-slate-400 text-center leading-tight">Instrucciones especiales</span>
                    </button>

                    {{-- Botón: Descuento --}}
                    <button
                        type="button"
                        wire:click="irADescuento"
                        class="flex flex-col items-center justify-center gap-2 rounded-2xl border border-rose-700/40 bg-rose-900/20 hover:bg-rose-900/40 active:scale-[0.98] transition p-5"
                    >
                        <span class="text-3xl">🏷️</span>
                        <span class="font-bold text-base text-rose-300">Descuento</span>
                        <span class="text-xs text-rose-400/70 text-center leading-tight">Aplicar % al platillo</span>
                    </button>
                </div>

                <div class="text-xs text-slate-500 mt-4 text-center">
                    Toca afuera para cerrar.
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════════
         MODAL: Descuento
    ══════════════════════════════════════════════════════ --}}
    @if($showDescuentoModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/70" wire:click="cerrarDescuento"></div>

            <div class="relative w-full max-w-sm rounded-2xl bg-slate-900 border border-slate-800 shadow-2xl p-5">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div>
                        <div class="text-sm text-slate-400">Descuento para</div>
                        <div class="text-xl font-black">{{ $descuentoNombre }}</div>
                    </div>

                    <button
                        type="button"
                        wire:click="cerrarDescuento"
                        class="w-10 h-10 rounded-xl bg-slate-800 hover:bg-slate-700 font-black text-xl flex items-center justify-center flex-shrink-0"
                    >
                        ✕
                    </button>
                </div>

                {{-- Input de porcentaje --}}
                <div class="relative mb-1">
                    <input
                        type="number"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        min="0"
                        max="100"
                        step="1"
                        wire:model.live="descuentoTexto"
                        placeholder="0"
                        class="w-full rounded-2xl bg-slate-950/60 border border-slate-700 px-4 py-4 pr-14
                               text-3xl font-black text-center outline-none
                               focus:ring-2 focus:ring-rose-600/60 focus:border-rose-600/60
                               [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                    />
                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-2xl font-black text-slate-400 pointer-events-none">
                        %
                    </span>
                </div>
                <div class="text-xs text-slate-500 mb-5 text-center">
                    Ingresa un número entero entre 0 y 100
                </div>

                {{-- Atajos rápidos --}}
                <div class="flex gap-2 mb-5">
                    @foreach([5, 10, 15, 20, 50] as $pct)
                        <button
                            type="button"
                            wire:click="$set('descuentoTexto', '{{ $pct }}')"
                            class="flex-1 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700
                                   py-2 text-sm font-bold transition active:scale-[0.97]"
                        >
                            {{ $pct }}%
                        </button>
                    @endforeach
                </div>

                <div class="flex items-center justify-between gap-3">
                    <button
                        type="button"
                        wire:click="quitarDescuento"
                        class="px-4 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 font-semibold transition text-sm"
                    >
                        Quitar descuento
                    </button>

                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            wire:click="cerrarDescuento"
                            class="px-4 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 font-semibold transition text-sm"
                        >
                            Cancelar
                        </button>

                        <button
                            type="button"
                            wire:click="guardarDescuento"
                            class="px-5 py-3 rounded-xl bg-rose-600/90 hover:bg-rose-600 font-bold transition text-sm"
                        >
                            Aplicar
                        </button>
                    </div>
                </div>

                <div class="text-xs text-slate-500 mt-3 text-center">
                    Toca afuera para cerrar.
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════════
         MODAL: Tamaño
    ══════════════════════════════════════════════════════ --}}
    @if($showTamanoModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/70" wire:click="cerrarTamanoModal"></div>

            <div class="relative w-full max-w-xl rounded-2xl bg-slate-900 border border-slate-800 shadow-2xl p-5">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div>
                        <div class="text-sm text-slate-400">Selecciona tamaño</div>
                        <div class="text-xl font-black">{{ $tamanoPlatilloNombre }}</div>
                    </div>

                    <button
                        type="button"
                        wire:click="cerrarTamanoModal"
                        class="w-12 h-12 rounded-xl bg-slate-800 hover:bg-slate-700 font-black text-2xl"
                    >
                        ✕
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($tamanosOpciones as $op)
                        <button
                            type="button"
                            wire:click="seleccionarTamano({{ $op['tamano_id'] }})"
                            class="rounded-2xl border border-slate-800 bg-slate-950/40 hover:bg-slate-950/60 p-4 text-left transition active:scale-[0.99]"
                        >
                            <div class="font-bold text-lg">{{ $op['tamano_nombre'] }}</div>
                            <div class="text-slate-300 mt-1 font-black text-xl">
                                L {{ number_format((float)$op['precio'], 2) }}
                            </div>
                        </button>
                    @endforeach
                </div>

                <div class="text-xs text-slate-500 mt-4">
                    Tip: tocar afuera también cierra el modal.
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════════
         MODAL: Nota
    ══════════════════════════════════════════════════════ --}}
    @if($showNotaModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/70" wire:click="cerrarNotas"></div>

            <div class="relative w-full max-w-xl rounded-2xl bg-slate-900 border border-slate-800 shadow-2xl p-5">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div>
                        <div class="text-sm text-slate-400">Notas para</div>
                        <div class="text-xl font-black">{{ $notaTitulo }}</div>
                    </div>

                    <button
                        type="button"
                        wire:click="cerrarNotas"
                        class="w-12 h-12 rounded-xl bg-slate-800 hover:bg-slate-700 font-black text-2xl"
                    >
                        ✕
                    </button>
                </div>

                <textarea
                    wire:model.live="notaTexto"
                    rows="4"
                    placeholder="Ej: sin cebolla, bien cocido, sin hielo, aparte salsa..."
                    class="w-full rounded-2xl bg-slate-950/60 border border-slate-800 p-3 outline-none focus:ring-2 focus:ring-slate-600"
                ></textarea>

                <div class="flex items-center justify-between gap-3 mt-4">
                    <button
                        type="button"
                        wire:click="limpiarNotas"
                        class="px-4 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 font-semibold transition"
                    >
                        Quitar nota
                    </button>

                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            wire:click="cerrarNotas"
                            class="px-4 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 font-semibold transition"
                        >
                            Cancelar
                        </button>

                        <button
                            type="button"
                            wire:click="guardarNotas"
                            class="px-5 py-3 rounded-xl bg-emerald-600/90 hover:bg-emerald-600 font-bold transition"
                        >
                            Guardar
                        </button>
                    </div>
                </div>

                <div class="text-xs text-slate-500 mt-3">
                    Tip: tocar afuera también cierra.
                </div>
            </div>
        </div>
    @endif
</div>