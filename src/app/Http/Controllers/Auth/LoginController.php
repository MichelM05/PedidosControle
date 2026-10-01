<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegistroRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LoginController extends Controller
{
    public function create()
    {
        return Inertia::render('Auth/Login', [
            'registroAberto' => config('app.registro_aberto'),
            'primeiroAcesso' => ! User::exists(), // o primeiro cadastro vira administrador
        ]);
    }

    public function store(LoginRequest $request)
    {
        // Só entra quem está ativo; a mensagem é a mesma para senha errada, e-mail inexistente ou conta desativada
        $entrou = Auth::attempt(
            ['email' => $request->validated('email'), 'password' => $request->validated('password'), 'ativo' => true],
            $request->boolean('remember'),
        );

        if (! $entrou) {
            return back()->withErrors(['email' => 'E-mail ou senha incorretos.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('pedidos.index'));
    }

    /** Cria a própria conta (se o cadastro estiver aberto) e já entra. O primeiro usuário do sistema é administrador. */
    public function registrar(RegistroRequest $request)
    {
        abort_unless(config('app.registro_aberto'), 404);

        $usuario = User::create([...$request->validated(), 'is_admin' => ! User::exists()]);

        Auth::login($usuario);
        $request->session()->regenerate();

        return redirect()->route('pedidos.index')->with('success', 'Conta criada. Bem-vindo(a), '.$usuario->name.'!');
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
