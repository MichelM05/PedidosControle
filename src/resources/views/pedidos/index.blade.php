@extends('layouts.app')

@use('App\Helpers\Formatar')

@section('title', 'Pedidos')

@section('content')
    {{-- Card de upload --}}
    @include('pedidos.partials.upload-card')

    {{-- Filtros --}}
    @include('pedidos.partials.filtros')

    {{-- Lista de pedidos --}}
    <div class="card">
        <h2>Pedidos importados</h2>

        @if($pedidos->isEmpty())
            <div class="empty-state">
                <p class="empty-state-text">
                    {{ collect($filtros ?? [])->filter()->isNotEmpty() ? 'Nenhum pedido encontrado com esses filtros.' : 'Nenhum pedido ainda. Envie um PDF acima.' }}
                </p>
            </div>
        @else
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Nº pedido</th>
                            <th>Data</th>
                            <th>Cliente</th>
                            <th>Fornecedor</th>
                            <th class="num">Itens</th>
                            <th class="num">Total</th>
                            <th class="acoes">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pedidos as $pedido)
                            <tr>
                                <td>{{ Formatar::texto($pedido->numero) }}</td>
                                <td>{{ Formatar::data($pedido->data_pedido) }}</td>
                                <td>{{ Formatar::texto($pedido->cliente) }}</td>
                                <td>{{ Formatar::texto($pedido->fornecedor) }}</td>
                                <td class="num">{{ $pedido->itens_count }}</td>
                                <td class="num">{{ Formatar::moeda($pedido->valor) }}</td>
                                <td class="acoes">
                                    <a href="{{ route('pedidos.show', $pedido) }}" class="btn btn-actions">Ver detalhes</a>
                                    <a href="{{ route('pedidos.edit', $pedido) }}" class="btn btn-actions">Editar</a>
                                    <x-delete-button :action="route('pedidos.destroy', $pedido)"
                                                     :message="'Excluir o pedido nº ' . ($pedido->numero ?? $pedido->id) . '? Esta ação não pode ser desfeita.'"
                                                     title="Excluir pedido" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $pedidos->links() }}
        @endif
    </div>
@endsection

{{-- CSS e JS específicos da tela de upload --}}
@push('styles')
    @vite(['resources/css/upload.css'])
@endpush

@push('scripts')
    @vite(['resources/js/upload.js'])
@endpush
