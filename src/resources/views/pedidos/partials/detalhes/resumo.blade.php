@use('App\Helpers\Formatar')
<div class="pedido-resumo">
    <div class="resumo-titulo">
        <span class="resumo-label">Pedido</span>
        <span class="resumo-numero">nº {{ Formatar::texto($pedido->numero) }}</span>
        <span class="resumo-data">{{ Formatar::data($pedido->data_pedido) }}</span>
    </div>
    <div class="resumo-total">
        <button type="button" class="btn-editar btn-editar-claro" data-open-modal="edit-resumo">Editar</button>
        <span class="resumo-label">Total</span>
        <span class="resumo-valor">{{ Formatar::moeda($pedido->valor) }}</span>
        <span class="resumo-data">{{ $pedido->itens->count() }} {{ $pedido->itens->count() === 1 ? 'item' : 'itens' }}</span>
    </div>
</div>
