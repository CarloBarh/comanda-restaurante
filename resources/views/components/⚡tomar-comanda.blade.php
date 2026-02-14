<?php

use Livewire\Component;
use App\Models\Comanda;
use App\Models\Categoria;
use App\Models\Platillo;

new class extends Component
{
    public int $comandaId;
    public ?int $categoriaId = null;
    public string $busqueda = '';

    public function mount(int $comanda): void
    {
        $this->comandaId = $comanda;

        // Categoría por defecto (primera)
        $first = Categoria::query()->orderBy('id')->first();
        $this->categoriaId = $first?->id;
    }

    public function with(): array
    {
        $comanda = Comanda::with(['mesa','mesero'])->findOrFail($this->comandaId);

        $categorias = Categoria::query()
            ->orderBy('nombre')
            ->get();

        $platillos = Platillo::query()
            ->when($this->categoriaId, fn($q) => $q->where('categoria_id', $this->categoriaId))
            ->when($this->busqueda !== '', fn($q) => $q->where('nombre', 'like', '%'.$this->busqueda.'%'))
            ->orderBy('nombre')
            ->get();

        return compact('comanda', 'categorias', 'platillos');
    }

    public function seleccionarCategoria(int $categoriaId): void
    {
        $this->categoriaId = $categoriaId;
        $this->busqueda = '';
    }

    // Helper para la imagen (ajústalo según cómo guardes "imagen")
    public function imagenUrl(?string $imagen): string
    {
        if (! $imagen) return 'https://via.placeholder.com/300x200?text=Sin+Imagen';

        // si ya es URL completa:
        if (str_starts_with($imagen, 'http')) return $imagen;

        // si está en storage/app/public:
        return asset('storage/'.$imagen);
    }
};
?>

<div class="min-h-screen bg-slate-950 text-white">
    <div class="h-screen flex">

        {{-- Sidebar categorías --}}
        <aside class="w-64 md:w-72 bg-slate-900/60 border-r border-slate-800 p-4 flex flex-col">
            <div class="mb-4">
                <div class="text-xs text-slate-400">Mesa</div>
                <div class="text-2xl font-black">{{ $comanda->mesa->numero ?? 'N/A' }}</div>
                <div class="text-xs text-slate-400 mt-2">Comanda #{{ $comanda->id }}</div>
                <div class="text-xs text-slate-400">Estado: {{ $comanda->estado }}</div>
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
                <button
                    type="button"
                    class="w-full rounded-xl bg-emerald-600/90 hover:bg-emerald-600 px-4 py-3 font-bold transition"
                >
                    Enviar a cocina
                </button>

                <button
                    type="button"
                    class="w-full mt-2 rounded-xl bg-slate-800 hover:bg-slate-700 px-4 py-3 font-semibold transition"
                    onclick="window.location='{{ route('mesas') }}'"
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
                    <p class="text-slate-400 mt-1">
                        Toca un platillo para agregarlo a la comanda
                    </p>
                </div>
            </div>

            @if($platillos->isEmpty())
                <div class="text-slate-400">No hay platillos en esta categoría.</div>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                    @foreach($platillos as $p)
                        <button
                            type="button"
                            class="rounded-2xl overflow-hidden border border-slate-800 bg-slate-900/40 hover:bg-slate-900/60 transition active:scale-[0.99]"
                            {{-- Luego aquí haremos wire:click="agregarPlatillo({{ $p->id }})" --}}
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
            @endif
        </main>
    </div>
</div>
