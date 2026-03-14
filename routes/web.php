<?php

use Illuminate\Support\Facades\Route;



Route::redirect('/', '/pin');
Route::livewire('/pin', 'pin-pad')->name('pin');

Route::middleware('mesero.pin')->group(function () {
    Route::livewire('/mesas', 'mesas-board')->name('mesas');
    Route::livewire('/comandas/mesa/{mesa}', 'tomar-comanda')->name('comandas.tomar');

});


Route::livewire('/cocina', 'monitor-cocina')->name('cocina');
Route::livewire('/pizzeria', 'monitor-pizzeria')->name('pizzeria');
Route::livewire('/bebidas', 'monitor-bebidas')->name('bebidas');
Route::livewire('/finalizadas', 'comandas-finalizadas')->name('finalizadas');
Route::livewire('/monitor-general', 'monitor-general')->name('monitor.general');
Route::livewire('/historico', 'historico-pedidos')->name('historico');
Route::livewire('/facturas','facturas')->name('facturas');
Route::livewire('/facturas/{factura}', 'factura-detalles')->name('facturas.detalle');

