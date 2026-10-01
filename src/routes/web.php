<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ControleController;
use App\Http\Controllers\HistoricoController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

// Login (só para quem ainda não entrou)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    Route::post('/registro', [LoginController::class, 'registrar'])->name('registro.store');
});

// Tudo o mais exige usuário logado
Route::middleware(['auth', 'ativo'])->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // Meu perfil
    Route::get('/perfil', [PerfilController::class, 'edit'])->name('perfil.edit');
    Route::patch('/perfil', [PerfilController::class, 'update'])->name('perfil.update');
    Route::put('/perfil/senha', [PerfilController::class, 'senha'])->name('perfil.senha');

    // Usuários (administradores)
    Route::middleware('admin')->group(function () {
        Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
        Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::patch('/usuarios/{usuario}', [UsuarioController::class, 'update'])->name('usuarios.update');
    });

    // Pedidos
    Route::get('/', [PedidoController::class, 'index'])->name('pedidos.index');
    Route::post('/upload', [PedidoController::class, 'upload'])->name('pedidos.upload');
    Route::get('/pedidos/{pedido}/pdf', [PedidoController::class, 'pdf'])->name('pedidos.pdf');
    Route::patch('/pedidos/{pedido}/dados', [PedidoController::class, 'atualizarDados'])->name('pedidos.dados');
    Route::resource('pedidos', PedidoController::class)->except(['index']);

    // Histórico de alterações (quem, o quê e quando)
    Route::get('/historico', [HistoricoController::class, 'index'])->name('historico.index');

    // Controle de pedidos (planilha): grade por ano, edição do controle do item, status e exportação .xlsx
    Route::get('/controle', [ControleController::class, 'index'])->name('controle.index');
    Route::get('/controle/exportar', [ControleController::class, 'exportar'])->name('controle.exportar');
    Route::patch('/controle/itens/{item}', [ControleController::class, 'atualizar'])->name('controle.atualizar');
    Route::patch('/pedidos/{pedido}/status', [ControleController::class, 'atualizarStatusPedido'])->name('pedidos.status');
});
