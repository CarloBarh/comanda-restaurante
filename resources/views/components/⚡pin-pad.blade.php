<?php

use Livewire\Component;
use App\Models\Mesero;

new class extends Component
{
    public string $pin = '';
    public string $error = '';

    public function addDigit(int $digit): void
    {
        if (strlen($this->pin) < 4) {
            $this->pin .= (string) $digit;
        }

        if (strlen($this->pin) === 4) {
            $this->validatePin();
        }
    }

    public function backspace(): void
    {
        $this->pin = substr($this->pin, 0, -1);
        $this->error = '';
    }

    public function clearPin(): void
    {
        $this->pin = '';
        $this->error = '';
    }

    public function validatePin()
    {
        $mesero = Mesero::query()
            ->where('pin', $this->pin)
            ->where('activo', true)
            ->first();

        if (! $mesero) {
            $this->error = 'PIN incorrecto';
            $this->pin = '';
            return;
        }

        session(['mesero_id' => $mesero->id]);

        return redirect()->route('mesas');
    }
};
?>

<div class="min-h-screen bg-slate-950 text-white flex flex-col">
@include('partials.navbar')

    {{-- CONTENIDO --}}
    <div class="flex-1 flex items-center justify-center p-4">
        <div class="w-full max-w-3xl bg-slate-900/60 border border-slate-800 rounded-2xl p-6 md:p-10 shadow-2xl">
            <div class="flex items-center justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold, font-family: 'Courier New', monospace;">Ingresar PIN</h1>
                    <p class="text-slate-400 mt-1">Para habilitar la estación</p>
                </div>
                <button wire:click="clearPin"
                    class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 active:scale-[0.99] transition">
                    Limpiar
                </button>
            </div>

            {{-- Display de PIN (••••) --}}
            <div class="flex justify-center mb-4">
                <div class="flex gap-4 ">
                    @for ($i = 0; $i < 4; $i++)
                        <div class="w-5 h-5 md:w-6 md:h-6 rounded-full border-2 border-slate-400
                            {{ strlen($pin) > $i ? 'bg-white border-white' : '' }}">
                        </div>
                    @endfor
                </div>
            </div>

            @if ($error)
                <div class="text-center text-red-400 mb-4 font-semibold">{{ $error }}</div>
            @else
                <div class="text-center text-slate-500 mb-4">Ingresa 4 dígitos</div>
            @endif

            {{-- Keypad --}}
            <div class="grid grid-cols-3 gap-3 md:gap-4">
                @foreach ([1,2,3,4,5,6,7,8,9] as $n)
                    <button wire:click="addDigit({{ $n }})"
                        class="py-5 md:py-7 rounded-2xl bg-slate-800 hover:bg-slate-700 active:scale-[0.99] transition
                               text-2xl md:text-3xl font-bold, font-family: 'Courier New', monospace;">
                        {{ $n }}
                    </button>
                @endforeach

                <button wire:click="backspace"
                    class="py-5 md:py-7 rounded-2xl bg-rose-600/90 hover:bg-rose-600 active:scale-[0.99] transition
                           text-lg md:text-xl font-bold">
                    Borrar
                </button>

                <button wire:click="addDigit(0)"
                    class="py-5 md:py-7 rounded-2xl bg-slate-800 hover:bg-slate-700 active:scale-[0.99] transition
                           text-2xl md:text-3xl font-bold">
                    0
                </button>

                <button wire:click="validatePin"
                    class="py-5 md:py-7 rounded-2xl bg-emerald-600/90 hover:bg-emerald-600 active:scale-[0.99] transition
                           text-lg md:text-xl font-bold">
                    Entrar
                </button>
            </div>
        </div>
    </div>

</div>