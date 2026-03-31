@extends('layouts.app')

@section('title', 'Detalhes do Pedido')

@section('content')
    <div class="card">
        <a href="{{ route('pedidos.index') }}" class="btn-back">
            Voltar para lista
        </a>

        <a href="{{ route('pedidos.edit', $pedido) }}" class="btn-back">
            Editar
        </a>

        <h2>Detalhes do Pedido</h2>

        @include('pedidos.partials.pedido-card', ['pedido' => $pedido])
    </div>
@endsection

