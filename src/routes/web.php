<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PedidoController;

// Rota de Index
Route::get('/', [PedidoController::class, 'index'])->name('pedidos.index');

// Rota de Upload de PDF
Route::post('/upload', [PedidoController::class, 'upload'])->name('pedidos.upload');

// Rota de Criar (Formulário) - Deve vir antes da dinâmica {pedido}
Route::get('/pedidos/create', [PedidoController::class, 'create'])->name('pedidos.create');

// Rota para Salvar (POST) - Nomeada como save a pedido do usuário
Route::post('/pedidos', [PedidoController::class, 'save'])->name('pedidos.save');

// Rota para Salvar Edição (POST/PUT) - Reutilizando o save
Route::post('/pedidos/{id}', [PedidoController::class, 'save'])->name('pedidos.update_save');

// Rota de Editar (GET)
Route::get('/pedidos/{pedido}/edit', [PedidoController::class, 'edit'])->name('pedidos.edit');

// Rota de Ver Detalhes (Dinâmica)
Route::get('/pedidos/{pedido}', [PedidoController::class, 'show'])->name('pedidos.show');

// Rota de Excluir
Route::delete('/pedidos/{pedido}', [PedidoController::class, 'delete'])->name('pedidos.delete');
