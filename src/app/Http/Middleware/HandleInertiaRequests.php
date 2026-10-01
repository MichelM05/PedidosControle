<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    /**
     * Props compartilhadas com todas as páginas React.
     * Os erros de validação já são enviados pelo Inertia em `errors`.
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => ['user' => fn () => $request->user()?->only(['id', 'name', 'email', 'is_admin'])],
            'flash' => ['success' => fn () => $request->session()->get('success')],
        ];
    }
}
