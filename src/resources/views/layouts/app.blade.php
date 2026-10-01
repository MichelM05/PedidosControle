<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Pedidos')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body>
    <div class="navbar">
        <div class="navbar-container">
            <a href="{{ route('pedidos.index') }}" class="navbar-brand">
                PDF Transformer
            </a>
        </div>
    </div>

    <div class="container">
        @if(session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-error" role="alert">
                <strong>Verifique os dados:</strong>
                <ul>
                    @foreach($errors->all() as $erro)
                        <li>{{ $erro }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </div>

    {{-- Modal único de confirmação (usado por qualquer form com data-confirm) --}}
    <dialog id="confirm-dialog" class="modal" aria-labelledby="confirm-title">
        <form method="dialog" class="modal-body">
            <h3 id="confirm-title" class="modal-title">Confirmar ação</h3>
            <p id="confirm-message" class="modal-message"></p>
            <div class="modal-actions">
                <button type="submit" value="cancel" class="btn btn-secondary">Cancelar</button>
                <button type="submit" value="confirm" id="confirm-ok" class="btn btn-danger">Confirmar</button>
            </div>
        </form>
    </dialog>

    @stack('scripts')
</body>
</html>
