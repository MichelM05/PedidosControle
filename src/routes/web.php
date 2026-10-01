<?php

use App\Http\Controllers\PedidoController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PedidoController::class, 'index'])->name('pedidos.index');

Route::post('/upload', [PedidoController::class, 'upload'])->name('pedidos.upload');

Route::get('/pedidos/{pedido}/pdf', [PedidoController::class, 'pdf'])->name('pedidos.pdf');

Route::patch('/pedidos/{pedido}/dados', [PedidoController::class, 'atualizarDados'])->name('pedidos.dados');

Route::resource('pedidos', PedidoController::class)->except(['index']);
