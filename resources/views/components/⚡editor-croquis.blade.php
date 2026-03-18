<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Mesa;
use Illuminate\Support\Facades\Log;

new class extends Component
{
    public array $mesas = [];
    public bool $guardando = false;
    public string $mensaje = '';
    public string $tipoMensaje = 'success';

    public function with(): array
    {
        $mesas = Mesa::query()
            ->orderBy('zona')
            ->orderBy('numero_zona')
            ->get();

        $mesasPicnic = $mesas->where('zona', 'Picnic')->values();
        $mesasCroquis = $mesas->where('zona', '!=', 'Picnic')->values();

        return compact('mesasCroquis', 'mesasPicnic');
    }

    public function mount(): void
    {
        $this->mesas = Mesa::query()
            ->orderBy('zona')
            ->orderBy('numero_zona')
            ->get()
            ->map(fn ($mesa) => [
                'id' => $mesa->id,
                'numero' => $mesa->numero,
                'zona' => $mesa->zona,
                'numero_zona' => $mesa->numero_zona,
                'estado' => $mesa->estado,
                'pos_x' => (float) $mesa->pos_x,
                'pos_y' => (float) $mesa->pos_y,
            ])
            ->toArray();
    }

    public function guardar(array $posiciones): void
    {
        try {
            $this->guardando = true;
            
            foreach ($posiciones as $mesaId => $posicion) {
                Mesa::whereKey($mesaId)->update([
                    'pos_x' => round($posicion['x'], 2),
                    'pos_y' => round($posicion['y'], 2),
                ]);
            }

            $this->tipoMensaje = 'success';
            $this->mensaje = '✓ Posiciones guardadas correctamente';
            
            // Actualizar las mesas en memoria con las nuevas posiciones
            foreach ($posiciones as $mesaId => $posicion) {
                foreach ($this->mesas as &$mesa) {
                    if ($mesa['id'] == $mesaId) {
                        $mesa['pos_x'] = round($posicion['x'], 2);
                        $mesa['pos_y'] = round($posicion['y'], 2);
                        break;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error guardando posiciones de mesas: ' . $e->getMessage());
            $this->tipoMensaje = 'error';
            $this->mensaje = '✗ Error al guardar las posiciones';
        } finally {
            $this->guardando = false;
        }
    }
};
?>

<div
    class="min-h-screen bg-[#111315] text-white p-4 md:p-5"
    x-data="{
        dragging: null,
        offsetX: 0,
        offsetY: 0,
        coordenada: { x: 0, y: 0 },
        posiciones: {},
        
        inicializarPosiciones() {
            const mesas = document.querySelectorAll('[data-mesa-id]');
            mesas.forEach(mesa => {
                const id = parseInt(mesa.getAttribute('data-mesa-id'));
                this.posiciones[id] = {
                    x: parseFloat(mesa.style.left),
                    y: parseFloat(mesa.style.top)
                };
            });
        },

        startDrag(event, mesaId) {
            event.preventDefault();
            const el = event.currentTarget;
            el.setPointerCapture?.(event.pointerId);
            const lienzo = this.$refs.lienzo;
            const rect = lienzo.getBoundingClientRect();
            const elRect = el.getBoundingClientRect();

            el.classList.add('opacity-75', 'shadow-2xl', 'scale-110');
            el.style.zIndex = '50';

            this.dragging = { mesaId, el, rect };

            this.offsetX = event.clientX - elRect.left;
            this.offsetY = event.clientY - elRect.top;

            const move = (e) => {
                if (!this.dragging) return;

                const xPx = e.clientX - rect.left - this.offsetX;
                const yPx = e.clientY - rect.top - this.offsetY;

                const x = (xPx / rect.width) * 100;
                const y = (yPx / rect.height) * 100;

                const boundedX = Math.max(0, Math.min(94, x));
                const boundedY = Math.max(0, Math.min(92, y));

                el.style.left = boundedX + '%';
                el.style.top = boundedY + '%';
                
                // Actualizar coordenadas mostradas
                this.coordenada = { 
                    x: Math.round(boundedX * 10) / 10, 
                    y: Math.round(boundedY * 10) / 10 
                };
            };

            const up = async () => {
                if (!this.dragging) return;

                el.classList.remove('opacity-75', 'shadow-2xl', 'scale-110');
                el.style.zIndex = 'auto';

                const x = parseFloat(el.style.left);
                const y = parseFloat(el.style.top);

                // Guardar la posición en el objeto local (NO llamar a Livewire)
                this.posiciones[mesaId] = { x, y };

                window.removeEventListener('pointermove', move);
                window.removeEventListener('pointerup', up);

                el.releasePointerCapture?.(event.pointerId);

                el.blur();
                this.dragging = null;
                this.coordenada = { x: 0, y: 0 };
            };

            window.addEventListener('pointermove', move);
            window.addEventListener('pointerup', up);
        },
        
        guardarPosiciones() {
            // Enviar todas las posiciones a Livewire
            $wire.guardar(this.posiciones);
        }
    }"
    x-init="inicializarPosiciones()"
>
    <div class="max-w-[1200px] mx-auto">
        <div class="flex items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl md:text-[28px] font-black tracking-tight text-[#f5f1e8]">
                    Editor de Croquis
                </h1>
                <p class="text-sm text-[#b8b2a7] mt-1">
                    Arrastra las mesas para reposicionar y guarda los cambios
                </p>
            </div>

            <div class="flex items-center gap-3">
                @if($mensaje)
                    <div class="px-4 py-2.5 rounded-xl font-semibold text-sm transition-all
                        {{ $tipoMensaje === 'success' 
                            ? 'bg-emerald-500/20 border border-emerald-500/50 text-emerald-200' 
                            : 'bg-rose-500/20 border border-rose-500/50 text-rose-200' 
                        }}">
                        {{ $mensaje }}
                    </div>
                @endif

                <button
                    type="button"
                    @click="guardarPosiciones()"
                    wire:loading.attr="disabled"
                    wire:target="guardar"
                    class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 font-bold 
                        transition-all duration-200 active:scale-[0.98] flex items-center gap-2
                        disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span wire:loading.remove wire:target="guardar">💾 Guardar</span>
                    <span wire:loading wire:target="guardar" class="flex items-center gap-1">
                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Guardando...
                    </span>
                </button>
            </div>
        </div>

        <div
            x-ref="lienzo"
            class="relative w-full rounded-[28px] border shadow-2xl overflow-hidden group"
            style="
                aspect-ratio: 4 / 3;
                background: linear-gradient(180deg, #181b1f 0%, #121417 100%);
                border-color:#2a2f36;
            "
        >
            {{-- Indicador de coordenadas al arrastrar --}}
            <div x-show="dragging" class="absolute top-4 right-4 bg-black/60 px-4 py-2 rounded-lg border border-amber-500/50 z-40 text-amber-200 text-sm font-mono">
                <div>X: <span x-text="coordenada.x.toFixed(1)"></span>%</div>
                <div>Y: <span x-text="coordenada.y.toFixed(1)"></span>%</div>
            </div>

            {{-- Info de ayuda --}}
            <div class="absolute top-4 left-4 bg-black/50 px-3 py-1.5 rounded-lg text-xs text-slate-400 border border-slate-600/30">
                💡 Arrastra las mesas para reposicionar
            </div>
            {{-- Áreas --}}
            <div class="absolute rounded-[24px] border shadow-lg"
                 style="left: 29.6%; top: 4.4%; width: 35%; height: 44.5%; background: rgba(30,34,39,.70); border-color:#4a3f35;">
                <div class="absolute text-[#e8dcc6] text-[1.4vw] font-bold tracking-wide" style="left: 4%; top: 3%;">
                    Área Corredor
                </div>
                
            </div>

            <div class="absolute rounded-[20px] border"
                 style="left: 31.7%; top: 19.4%; width: 19.2%; height: 16.1%; background: rgba(42,47,54,.80); border-color:#5a5045;">
                <div class="absolute text-[#e8dcc6] text-[1.2vw] font-bold tracking-wide" style="left: 5%; top: 4%;">
                    Salón
                </div>
            </div>

            <div class="absolute rounded-[24px] border shadow-lg"
                 style="left: 3.3%; top: 51.7%; width: 34.6%; height: 40.5%; background: rgba(30,34,39,.70); border-color:#4a3f35;">
                <div class="absolute text-[#e8dcc6] text-[2vw] font-bold tracking-wide" style="left: 40%; top: 41%;">
                    Área Verde
                </div>
                <button
                    type="button"
                    wire:click="abrirPicnic"
                    class="absolute rounded-2xl border shadow-lg transition
                        active:scale-[0.97] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-950
                        bg-amber-600/20 border-amber-500/40 hover:bg-amber-600/30 focus:ring-amber-500"
                    style="
                        left: 45%;
                        top: 56%;
                        width: 28%;
                        height: 15%;
                    "
                    >
                    <div class="absolute inset-[10%]">
                        <div class="w-full h-[42%] rounded-t-2xl rounded-b-xl border bg-amber-500/25 border-amber-400/30"></div>
                        <div class="mt-[8%] w-full h-[32%] rounded-2xl border bg-amber-500/15 border-amber-400/25"></div>
                    </div>

                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="text-center leading-none">
                            <div class="text-[9px] text-slate-300">Zona</div>
                            <div class="text-[clamp(12px,1.2vw,18px)] font-black tracking-wide text-slate-100">
                                Picnic
                            </div>
                        </div>
                    </div>
                </button>
            </div>

            <div class="absolute rounded-[24px] border shadow-lg"
                 style="left: 39.6%; top: 51.7%; width: 25.8%; height: 40.5%; background: rgba(30,34,39,.70); border-color:#4a3f35;">
                <div class="absolute text-[#e8dcc6] text-[2vw] font-bold tracking-wide" style="left: 15%; top: 5%;">
                    Área de Fuente
                </div>
            </div>

            <div class="absolute rounded-[24px] border shadow-lg"
                 style="left: 67.1%; top: 51.7%; width: 26.7%; height: 40.5%; background: rgba(30,34,39,.70); border-color:#4a3f35;">
                <div class="absolute text-[#e8dcc6] text-[2vw] font-bold tracking-wide" style="left: 10%; top: 5%;">
                    Área de Niños
                </div>
            </div>

            {{-- Mesas arrastrables --}}
            @foreach($mesasCroquis as $mesa)
                @php $ocupada = $mesa['estado'] === 'ocupada'; @endphp

                <button
                    type="button"
                    data-mesa-id="{{ $mesa['id'] }}"
                    x-on:pointerdown.prevent="startDrag($event, {{ $mesa['id'] }})"
                    class="
                        absolute rounded-2xl border shadow-lg transition overflow-hidden cursor-grab
                        hover:shadow-xl hover:scale-105 active:cursor-grabbing
                        {{ $ocupada
                            ? 'bg-rose-600/25 border-rose-500/50 hover:bg-rose-600/35 hover:border-rose-500/60'
                            : 'bg-emerald-600/25 border-emerald-500/50 hover:bg-emerald-600/35 hover:border-emerald-500/60'
                        }}
                    "
                    style="
                        left: {{ $mesa['pos_x'] }}%;
                        top: {{ $mesa['pos_y'] }}%;
                        width: 5.6%;
                        height: 7.1%;
                        touch-action: none;
                        user-select: none;
                    "
                    title="{{ $mesa['zona'] }} - Mesa {{ $mesa['numero_zona'] }} - Pos: {{ $mesa['pos_x'] }}%, {{ $mesa['pos_y'] }}%"
                >
                    <div class="absolute inset-[10%] rounded-lg border opacity-50"></div>

                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <div class="text-center leading-tight">
                            <div class="text-[8px] text-slate-300 font-semibold">Mesa</div>
                            <div class="text-[clamp(13px,1.3vw,20px)] font-black tracking-wide">
                                {{ $mesa['numero_zona'] ?? $mesa['numero'] }}
                            </div>
                        </div>
                    </div>

                    {{-- Indicador de draggable --}}
                    <div class="absolute top-1 right-1 w-1.5 h-1.5 rounded-full opacity-50 hover:opacity-100 transition
                        {{ $ocupada ? 'bg-rose-300' : 'bg-emerald-300' }}"></div>
                </button>
            @endforeach
        </div>
    </div>
    
    <script>
        // Auto-limpia el mensaje de éxito/error después de 3 segundos
        setInterval(() => {
            document.querySelectorAll('[class*="bg-emerald-500/20"], [class*="bg-rose-500/20"]').forEach(el => {
                if (el.textContent.trim()) {
                    setTimeout(() => {
                        el.style.animation = 'fadeOut 0.3s ease-out';
                        setTimeout(() => el.remove(), 300);
                    }, 3000);
                }
            });
        }, 500);
    </script>
</div>