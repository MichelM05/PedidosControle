{{-- CSS e JS específicos --}}
@push('styles')
    @vite(['resources/css/pedidos/create.css'])
@endpush

@push('scripts')
    @vite(['resources/js/pedidos/create.js'])
@endpush

<div class="card shadow-lg">
    <h1 class="text-2xl font-bold mb-6">
        {{ $pedido->exists ? 'Editar Pedido Nº ' . $pedido->numero : 'Criar Pedido Manual' }}
    </h1>

    <form action="{{ $pedido->exists ? route('pedidos.update_save', $pedido->id) : route('pedidos.save') }}" method="POST">
        @csrf

        <div class="card bg-gray-50 p-6 border mb-8">
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label for="numero" class="font-bold">Número do Pedido</label>
                    <input type="text" name="numero" id="numero" class="form-control"
                           value="{{ old('numero', $pedido->numero) }}" placeholder="Ex: 123456">
                </div>
                <div class="form-group">
                    <label for="data_pedido" class="font-bold">Data do Pedido</label>
                    <input type="date" name="data_pedido" id="data_pedido" class="form-control"
                           value="{{ old('data_pedido', $pedido->data_pedido?->format('Y-m-d')) }}">
                </div>
                <div class="form-group">
                    <label for="cliente" class="font-bold">Cliente</label>
                    <input type="text" name="cliente" id="cliente" class="form-control"
                           value="{{ old('cliente', $pedido->cliente) }}" placeholder="Nome do comprador">
                </div>
                <div class="form-group">
                    <label for="fornecedor" class="font-bold">Fornecedor</label>
                    <input type="text" name="fornecedor" id="fornecedor" class="form-control"
                           value="{{ old('fornecedor', $pedido->fornecedor) }}" placeholder="Nome da empresa vendedora">
                </div>
                <div class="form-group">
                    <label for="valor" class="font-bold">Valor Total (R$)</label>
                    <input type="number" step="0.01" name="valor" id="valor" class="form-control"
                           value="{{ old('valor', $pedido->valor) }}" placeholder="0,00">
                </div>
            </div>
        </div>

        <!-- Seção de Itens -->
        <div class="mt-8">
            <h3 class="text-xl font-bold mb-4">Itens do Pedido</h3>

            <div id="itens-container">
                @php
                    $itens = old('itens', $pedido->itens->isEmpty() ? [[]] : $pedido->itens->toArray());
                @endphp

                @foreach($itens as $index => $item)
                    <div class="item-form-card card mb-4 p-4 border" style="background: white">
                        <div class="item-header">
                            <div class="font-bold"> Item #{{ $index + 1 }} </div>
                            <div>
                                <button type="button" class="btn-toggle-item" title="Minimizar/Expandir">-</button>
                                <button type="button" class="btn-remove-item" title="Remover Item">&times;</button>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="text-xs font-bold uppercase">Item</label>
                                <input type="text" name="itens[{{ $index }}][item]" class="form-control"
                                       value="{{ $item['item'] ?? '' }}" placeholder="10">
                            </div>
                            <div class="form-group">
                                <label class="text-xs font-bold uppercase">Material</label>
                                <input type="text" name="itens[{{ $index }}][material]" class="form-control"
                                       value="{{ $item['material'] ?? '' }}" placeholder="Cód. Material">
                            </div>
                            <div class="form-group">
                                <label class="text-xs font-bold uppercase">Denominação</label>
                                <input type="text" name="itens[{{ $index }}][denominacao]" class="form-control"
                                       value="{{ $item['denominacao'] ?? '' }}" placeholder="Descrição">
                            </div>
                            <div class="form-group">
                                <label class="text-xs font-bold uppercase">Quantidade</label>
                                <input type="number" step="0.0001" name="itens[{{ $index }}][qtd]" class="form-control"
                                       value="{{ $item['qtd'] ?? '' }}">
                            </div>
                            <div class="form-group">
                                <label class="text-xs font-bold uppercase">Unidade</label>
                                <input type="text" name="itens[{{ $index }}][un]" class="form-control"
                                       value="{{ $item['un'] ?? '' }}" placeholder="UN">
                            </div>
                            <div class="form-group">
                                <label class="text-xs font-bold uppercase">Preço Unit.</label>
                                <input type="number" step="0.0001" name="itens[{{ $index }}][preco]" class="form-control"
                                       value="{{ $item['preco'] ?? '' }}">
                            </div>
                            <div class="form-group">
                                <label class="text-xs font-bold uppercase">Valor Total</label>
                                <input type="number" step="0.0001" name="itens[{{ $index }}][vlr_tot]" class="form-control"
                                       value="{{ $item['vlr_tot'] ?? '' }}">
                            </div>
                            <div class="form-group">
                                <label class="text-xs font-bold uppercase">ICMS (%)</label>
                                <input type="number" step="0.0001" name="itens[{{ $index }}][icms]" class="form-control"
                                       value="{{ $item['icms'] ?? '' }}">
                            </div>
                            <div class="form-group">
                                <label class="text-xs font-bold uppercase">IPI (%)</label>
                                <input type="number" step="0.0001" name="itens[{{ $index }}][ipi]" class="form-control"
                                       value="{{ $item['ipi'] ?? '' }}">
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex gap-4 mt-6 items-center">
            <button type="button" id="add-item" class="btn" style="background-color: #10b981; color: white;">
                + Adicionar Item
            </button>
            <button type="submit" class="btn btn-primary">
                {{ $pedido->exists ? 'Salvar Alterações' : 'Salvar Lançamento' }}
            </button>
            <a href="{{ route('pedidos.index') }}" class="btn btn-secondary">
                Cancelar
            </a>
        </div>
    </form>
</div>
