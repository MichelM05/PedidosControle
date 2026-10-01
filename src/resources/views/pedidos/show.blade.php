@extends('layouts.app')

@section('title', 'Pedido nº ' . ($pedido->numero ?? $pedido->id))

@section('content')
    <div class="card">
        <div class="acoes-topo">
            <a href="{{ route('pedidos.index') }}" class="btn-back">&larr; Voltar</a>
            <div class="acoes-grupo">
                @if($pedido->arquivo_pdf)
                    <a href="{{ route('pedidos.pdf', $pedido) }}" target="_blank" rel="noopener" class="btn btn-secondary">Abrir PDF original</a>
                @endif
                <a href="{{ route('pedidos.edit', $pedido) }}" class="btn btn-secondary">Editar</a>
                <x-delete-button :action="route('pedidos.destroy', $pedido)"
                                 :message="'Excluir o pedido nº ' . ($pedido->numero ?? $pedido->id) . '? Esta ação não pode ser desfeita.'"
                                 title="Excluir pedido" class="btn btn-danger" />
            </div>
        </div>

        <h2>Detalhes do pedido</h2>

        @include('pedidos.partials.pedido-card', ['pedido' => $pedido])
    </div>
@endsection

@push('styles')
    @vite(['resources/css/pedidos/show.css'])
@endpush
