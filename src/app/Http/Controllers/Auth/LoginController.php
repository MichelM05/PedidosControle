<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegistroRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Inertia\Inertia;

class LoginController extends Controller
{
    public function create()
    {
        return Inertia::render('Auth/Login', [
            'registroAberto' => config('app.registro_aberto'),
            'primeiroAcesso' => config('app.primeiro_cadastro_admin') && ! User::exists(), // o primeiro cadastro vira administrador
        ]);
    }

    /** Tentativas de login erradas permitidas por e-mail + IP, e minutos de espera depois disso. */
    private const MAX_TENTATIVAS = 5;

    public function store(LoginRequest $request)
    {
        $chave = Str::lower($request->validated('email')).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($chave, self::MAX_TENTATIVAS)) {
            return back()->withErrors(['email' => 'Muitas tentativas de login. Tente novamente em '.ceil(RateLimiter::availableIn($chave) / 60).' minuto(s).'])->onlyInput('email');
        }

        // Só entra quem está ativo; a mensagem é a mesma para senha errada, e-mail inexistente ou conta desativada
        $entrou = Auth::attempt(
            ['email' => $request->validated('email'), 'password' => $request->validated('password'), 'ativo' => true],
            $request->boolean('remember'),
        );

        if (! $entrou) {
            RateLimiter::hit($chave, 15 * 60); // a contagem expira em 15 minutos

            return back()->withErrors(['email' => 'E-mail ou senha incorretos.'])->onlyInput('email');
        }

        RateLimiter::clear($chave);
        $request->session()->regenerate();

        return redirect()->intended(route('pedidos.index'));
    }

    /** Cria a própria conta (se o cadastro estiver aberto) e já entra. O primeiro usuário do sistema é administrador. */
    public function registrar(RegistroRequest $request)
    {
        abort_unless(config('app.registro_aberto'), 404);

        // Limita a criação de contas por IP (evita cadastro em massa)
        $chave = 'registro|'.$request->ip();
        if (RateLimiter::tooManyAttempts($chave, 10)) {
            return back()->withErrors(['email' => 'Muitos cadastros deste endereço. Tente novamente mais tarde.']);
        }
        RateLimiter::hit($chave, 60 * 60);

        $usuario = User::create([...$request->validated(), 'is_admin' => config('app.primeiro_cadastro_admin') && ! User::exists()]);

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
