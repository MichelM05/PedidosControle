{{-- CSS e JS específicos --}}
@push('styles')
    @vite(['resources/css/pedidos/form.css'])
@endpush

@push('scripts')
    @vite(['resources/js/pedidos/form.js'])
@endpush

@php
    $campos = [
        ['numero', 'Número do pedido', 'text', 'Ex: 4502006271'],
        ['data_pedido', 'Data do pedido', 'date', ''],
        ['cliente', 'Cliente', 'text', 'Nome do comprador'],
        ['fornecedor', 'Fornecedor', 'text', 'Nome da empresa vendedora'],
        ['valor', 'Valor total (R$)', 'number', '0,00'],
    ];
    $voltar = $pedido->exists ? route('pedidos.show', $pedido) : route('pedidos.index');
@endphp

<div class="card">
    <a href="{{ $voltar }}" class="btn-back">&larr; Voltar</a>

    <h2>{{ $pedido->exists ? 'Editar pedido nº ' . ($pedido->numero ?? $pedido->id) : 'Criar pedido manual' }}</h2>

    <form action="{{ $pedido->exists ? route('pedidos.update', $pedido) : route('pedidos.store') }}" method="POST">
        @csrf
        @if($pedido->exists)
            @method('PUT')
        @endif

        <div class="card form-resumo">
            <div class="grid grid-cols-2 gap-4 form-grid">
                @foreach($campos as [$nome, $rotulo, $tipo, $placeholder])
                    <div class="form-group">
                        <label for="{{ $nome }}" class="field-label">{{ $rotulo }}</label>
                        <input type="{{ $tipo }}" @if($nome === 'valor') step="0.01" @endif
                               name="{{ $nome }}" id="{{ $nome }}"
                               class="form-control {{ $errors->has($nome) ? 'is-invalid' : '' }}"
                               value="{{ old($nome, $nome === 'data_pedido' ? $pedido->data_pedido?->format('Y-m-d') : $pedido->$nome) }}"
                               @if($placeholder) placeholder="{{ $placeholder }}" @endif>
                        @error($nome)<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Itens --}}
        <div class="mt-8">
            <h3 class="secao-titulo">Itens do pedido</h3>

            <template id="item-template">
                @include('pedidos.partials.item-form', ['index' => '__INDEX__', 'item' => []])
            </template>

            <div id="itens-container">
                @php
                    $itens = old('itens', $pedido->itens->isEmpty() ? [[]] : $pedido->itens->toArray());
                    $itens = array_values($itens);
                @endphp

                @foreach($itens as $index => $item)
                    @include('pedidos.partials.item-form', ['index' => $index, 'item' => $item])
                @endforeach
            </div>
        </div>

        <div class="form-acoes">
            <button type="button" id="add-item" class="btn btn-secondary">+ Adicionar item</button>
            <div class="form-acoes-fim">
                <a href="{{ $voltar }}" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">{{ $pedido->exists ? 'Salvar alterações' : 'Criar pedido' }}</button>
            </div>
        </div>
    </form>
</div>
