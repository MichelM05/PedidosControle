    @extends('layouts.app')

@section('title', 'Pedidos')

@section('content')
    {{-- Card de upload --}}
    @include('pedidos.partials.upload-card')

    {{-- Filtros --}}
    @include('pedidos.partials.filtros')

    {{-- Lista de pedidos --}}
    <div class="card">
        <h2>
            <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
            Pedidos importados
        </h2>

        @if($pedidos->isEmpty())
            <div class="empty-state">
                <p class="empty-state-text">Nenhum pedido ainda. Envie um PDF acima.</p>
            </div>
        @else
            <td class="table-container">
                <table>
                    <thead>
                    <tr>
                        <th>Nº Pedido</th>
                        <th>Data</th>
                        <th>Cliente</th>
                        <th>Fornecedor</th>
                        <th>Qtd. Itens</th>
                        <th>Total</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pedidos as $pedido)
                        <tr>
                            <td>{{ $pedido->numero ?? '—' }}</td>
                            <td>
                                @if($pedido->data_pedido)
                                    {{ $pedido->data_pedido->format('d/m/Y') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $pedido->cliente ?: '—' }}</td>
                            <td>{{ $pedido->fornecedor ?: '—' }}</td>
                            <td>{{ $pedido->itens->count() }}</td>
                            <td>
                                {{ $pedido->valor !== null ? 'R$ ' . number_format((float) $pedido->valor, 2, ',', '.') : '—' }}
                            </td>

                            <td>
                                <a href="{{ route('pedidos.show', $pedido) }}" class="btn btn-actions">
                                    Ver detalhes
                                </a>

                                <a href="{{ route('pedidos.edit', $pedido) }}" class="btn btn-actions">
                                    Editar
                                </a>

                                <form action="{{ route('pedidos.delete', $pedido) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-actions btn-actions-delete" onclick="return confirm('Tem certeza que deseja excluir este pedido?')">
                                        Excluir
                                    </button>
                                </form>
                            </td>

                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
@endsection

{{-- Empurrando o CSS específico --}}
@push('styles')
    @vite(['resources/css/upload.css'])
@endpush

{{-- Empurrando o JS específico --}}
@push('scripts')
    @vite(['resources/js/upload.js'])
@endpush
