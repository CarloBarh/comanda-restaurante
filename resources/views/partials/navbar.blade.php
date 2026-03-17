<nav class="w-full" style="background:#0d1117; border-bottom:1px solid #1a2030; font-family:'Courier New', monospace;">
    <div class="max-w-screen-2xl mx-auto px-6 py-0 flex items-center h-14">

        {{-- Logo / Brand --}}
        <div class="flex items-center gap-3 flex-shrink-0">
            <div class="text-xl" style="filter:drop-shadow(0 0 8px #10b981);">🍕</div>
            <div class="font-black text-sm tracking-widest uppercase" style="color:#f1f5f9; letter-spacing:.15em;">
                Mi Pequeño Jardín
            </div>
        </div>

        {{-- Nav links — centrado --}}
        <div class="flex-1 flex items-center justify-center">
            <div class="flex items-center gap-1" x-data="{ monitores: false, reportes: false }">

                {{-- ── Dropdown: Monitores ───────────────────────── --}}
                <div class="relative" @mouseenter="monitores = true" @mouseleave="monitores = false">
                    <button
                        type="button"
                        class="flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-bold transition-all"
                        style="color: {{ request()->routeIs('monitor.*') || request()->routeIs('cocina') || request()->routeIs('pizzeria') || request()->routeIs('bebidas') ? '#10b981' : '#64748b' }};
                               background: {{ request()->routeIs('monitor.*') || request()->routeIs('cocina') || request()->routeIs('pizzeria') || request()->routeIs('bebidas') ? '#10b98118' : 'transparent' }};"
                    >
                        <span>📋</span>
                        <span>Monitores</span>
                        <svg class="w-3 h-3 transition-transform" :class="monitores ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div
                        x-show="monitores"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 translate-y-1"
                        class="absolute left-0 top-full pt-2 z-50 w-52"
                        style="display:none;"
                    >
                        <div class="rounded-2xl overflow-hidden py-1.5"
                             style="background:#111827; border:1px solid #1a2030;
                                    box-shadow:0 20px 40px rgba(0,0,0,0.5), 0 0 0 1px #10b98115;">

                            <a href="{{ route('monitor.general') }}"
                               class="flex items-center gap-3 px-4 py-2.5 text-sm font-semibold transition-colors
                                      {{ request()->routeIs('monitor.general') ? 'text-emerald-400' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                                <span class="text-base">🖥️</span>
                                <span>General</span>
                                @if(request()->routeIs('monitor.general'))
                                    <span class="ml-auto w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                @endif
                            </a>

                            <a href="{{ route('cocina') }}"
                               class="flex items-center gap-3 px-4 py-2.5 text-sm font-semibold transition-colors
                                      {{ request()->routeIs('cocina') ? 'text-orange-400' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                                <span class="text-base">🍳</span>
                                <span>Cocina</span>
                                @if(request()->routeIs('cocina'))
                                    <span class="ml-auto w-1.5 h-1.5 rounded-full bg-orange-400"></span>
                                @endif
                            </a>

                            <a href="{{ route('pizzeria') }}"
                               class="flex items-center gap-3 px-4 py-2.5 text-sm font-semibold transition-colors
                                      {{ request()->routeIs('pizzeria') ? 'text-red-400' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                                <span class="text-base">🍕</span>
                                <span>Pizzería</span>
                                @if(request()->routeIs('pizzeria'))
                                    <span class="ml-auto w-1.5 h-1.5 rounded-full bg-red-400"></span>
                                @endif
                            </a>

                            <a href="{{ route('bebidas') }}"
                               class="flex items-center gap-3 px-4 py-2.5 text-sm font-semibold transition-colors
                                      {{ request()->routeIs('bebidas') ? 'text-blue-400' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                                <span class="text-base">🥤</span>
                                <span>Bebidas</span>
                                @if(request()->routeIs('bebidas'))
                                    <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                                @endif
                            </a>
                        </div>
                    </div>
                </div>

                {{-- ── Dropdown: Reportes ────────────────────────── --}}
                <div class="relative" @mouseenter="reportes = true" @mouseleave="reportes = false">
                    <button
                        type="button"
                        class="flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-bold transition-all"
                        style="color: {{ request()->routeIs('finalizadas') || request()->routeIs('historico') || request()->routeIs('facturas') || request()->routeIs('facturas.detalle') || request()->routeIs('gastos') || request()->routeIs('caja') ? '#a78bfa' : '#64748b' }};
                               background: {{ request()->routeIs('finalizadas') || request()->routeIs('historico') || request()->routeIs('facturas') || request()->routeIs('facturas.detalle') || request()->routeIs('gastos') || request()->routeIs('caja') ? '#a78bfa18' : 'transparent' }};"
                    >
                        <span>📊</span>
                        <span>Reportes</span>
                        <svg class="w-3 h-3 transition-transform" :class="reportes ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div
                        x-show="reportes"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 translate-y-1"
                        class="absolute left-0 top-full pt-2 z-50 w-52"
                        style="display:none;"
                    >
                        <div class="rounded-2xl overflow-hidden py-1.5"
                             style="background:#111827; border:1px solid #1a2030;
                                    box-shadow:0 20px 40px rgba(0,0,0,0.5), 0 0 0 1px #a78bfa15;">

                            <a href="{{ route('finalizadas') }}"
                               class="flex items-center gap-3 px-4 py-2.5 text-sm font-semibold transition-colors
                                      {{ request()->routeIs('finalizadas') ? 'text-violet-400' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                                <span class="text-base">✅</span>
                                <span>Finalizadas</span>
                                @if(request()->routeIs('finalizadas'))
                                    <span class="ml-auto w-1.5 h-1.5 rounded-full bg-violet-400"></span>
                                @endif
                            </a>

                            <a href="{{ route('historico') }}"
                               class="flex items-center gap-3 px-4 py-2.5 text-sm font-semibold transition-colors
                                      {{ request()->routeIs('historico') ? 'text-violet-400' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                                <span class="text-base">🗂️</span>
                                <span>Histórico</span>
                                @if(request()->routeIs('historico'))
                                    <span class="ml-auto w-1.5 h-1.5 rounded-full bg-violet-400"></span>
                                @endif
                            </a>

                            <div style="height:1px; background:#1a2030; margin:4px 16px;"></div>

                            <a href="{{ route('facturas') }}"
                               class="flex items-center gap-3 px-4 py-2.5 text-sm font-semibold transition-colors
                                      {{ request()->routeIs('facturas') || request()->routeIs('facturas.detalle') ? 'text-violet-400' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                                <span class="text-base">🧾</span>
                                <span>Facturas</span>
                                @if(request()->routeIs('facturas') || request()->routeIs('facturas.detalle'))
                                    <span class="ml-auto w-1.5 h-1.5 rounded-full bg-violet-400"></span>
                                @endif
                            </a>

                            <div style="height:1px; background:#1a2030; margin:4px 16px;"></div>

                            <a href="{{ route('gastos') }}"
                               class="flex items-center gap-3 px-4 py-2.5 text-sm font-semibold transition-colors
                                      {{ request()->routeIs('gastos') ? 'text-red-400' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                                <span class="text-base">💸</span>
                                <span>Gastos</span>
                                @if(request()->routeIs('gastos'))
                                    <span class="ml-auto w-1.5 h-1.5 rounded-full bg-red-400"></span>
                                @endif
                            </a>

                            <a href="{{ route('caja') }}"
                               class="flex items-center gap-3 px-4 py-2.5 text-sm font-semibold transition-colors
                                      {{ request()->routeIs('caja') ? 'text-emerald-400' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                                <span class="text-base">🏦</span>
                                <span>Caja</span>
                                @if(request()->routeIs('caja'))
                                    <span class="ml-auto w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                @endif
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Divider --}}
                <div class="w-px h-5 mx-2" style="background:#1a2030;"></div>

                {{-- PIN --}}
                <a href="{{ route('pin') }}"
                   class="flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-bold transition-all text-slate-500 hover:text-white hover:bg-white/5">
                    <span>📲</span>
                    <span>PIN</span>
                </a>

            </div>
        </div>
    </div>
</nav>
