<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/pin', 'pin-pad')->name('pin');

Route::middleware('mesero.pin')->group(function () {
    Route::livewire('/mesas', 'mesas-board')->name('mesas');
    Route::livewire('/comandas/mesa/{mesa}', 'tomar-comanda')->name('comandas.tomar');

});


Route::livewire('/cocina', 'monitor-cocina')->name('cocina');
Route::livewire('/pizzeria', 'monitor-pizzeria')->name('pizzeria');
Route::livewire('/bebidas', 'monitor-bebidas')->name('bebidas');
Route::livewire('/finalizadas', 'comandas-finalizadas')->name('finalizadas');

