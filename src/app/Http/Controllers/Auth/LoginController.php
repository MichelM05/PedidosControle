<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Inertia\Inertia;

class LoginController extends Controller
{
    public function create()
    {
        return Inertia::render('Auth/Login');
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

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
