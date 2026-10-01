<?php

namespace App\Http\Controllers;

use App\Http\Requests\SalvarUsuarioRequest;
use App\Models\User;
use Inertia\Inertia;

/** Gestão de usuários (somente administradores). Usuários não são apagados: são desativados. */
class UsuarioController extends Controller
{
    public function index()
    {
        return Inertia::render('Usuarios/Index', [
            'usuarios' => User::orderBy('name')->get(['id', 'name', 'email', 'is_admin', 'ativo', 'created_at']),
        ]);
    }

    public function store(SalvarUsuarioRequest $request)
    {
        User::create($request->validated());

        return back()->with('success', 'Usuário criado.');
    }

    public function update(SalvarUsuarioRequest $request, User $usuario)
    {
        $dados = $request->validated();
        if (empty($dados['password'])) {
            unset($dados['password']); // senha em branco mantém a atual
        }

        if (($usuario->is_admin && $usuario->ativo) && (! ($dados['is_admin'] ?? false) || ! ($dados['ativo'] ?? true)) && $this->ultimoAdministrador($usuario)) {
            return back()->withErrors(['ativo' => 'Este é o único administrador ativo: não dá para removê-lo nem desativá-lo.']);
        }

        $usuario->update($dados);

        return back()->with('success', 'Usuário atualizado.');
    }

    private function ultimoAdministrador(User $usuario): bool
    {
        return ! User::where('is_admin', true)->where('ativo', true)->whereKeyNot($usuario->id)->exists();
    }
}
