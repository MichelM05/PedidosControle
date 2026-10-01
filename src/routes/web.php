<?php

use App\Http\Controllers\ControleController;
use App\Http\Controllers\PedidoController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PedidoController::class, 'index'])->name('pedidos.index');

Route::post('/upload', [PedidoController::class, 'upload'])->name('pedidos.upload');

Route::get('/pedidos/{pedido}/pdf', [PedidoController::class, 'pdf'])->name('pedidos.pdf');

Route::patch('/pedidos/{pedido}/dados', [PedidoController::class, 'atualizarDados'])->name('pedidos.dados');

Route::resource('pedidos', PedidoController::class)->except(['index']);

// Controle de pedidos (planilha): grade por ano, edição por célula e exportação .xlsx
Route::get('/controle', [ControleController::class, 'index'])->name('controle.index');
Route::get('/controle/exportar', [ControleController::class, 'exportar'])->name('controle.exportar');
Route::patch('/controle/itens/{item}', [ControleController::class, 'atualizar'])->name('controle.atualizar');
Route::get('/pedidos/{pedido}/exportar', [ControleController::class, 'exportarPedido'])->name('pedidos.exportar');
