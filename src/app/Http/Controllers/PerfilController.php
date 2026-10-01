<?php

namespace App\Http\Controllers;

use App\Http\Requests\AlterarSenhaRequest;
use App\Http\Requests\AtualizarPerfilRequest;
use Inertia\Inertia;

/** Meu perfil: o próprio usuário troca nome, e-mail e senha. */
class PerfilController extends Controller
{
    public function edit()
    {
        return Inertia::render('Perfil/Edit');
    }

    public function update(AtualizarPerfilRequest $request)
    {
        $request->user()->update($request->validated());

        return back()->with('success', 'Perfil atualizado.');
    }

    public function senha(AlterarSenhaRequest $request)
    {
        $request->user()->update(['password' => $request->validated('password')]);

        return back()->with('success', 'Senha alterada.');
    }
}
