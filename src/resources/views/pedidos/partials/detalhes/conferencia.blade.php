@use('App\Helpers\Formatar')
{{-- Só aparece quando o total do PDF não bate com a soma dos itens --}}
@if($pedido->valor !== null && abs($somaItens - (float) $pedido->valor) >= 0.01)
    <div class="conferencia divergente">
        <div><span class="label">Total do pedido (no PDF)</span>{{ Formatar::moeda($pedido->valor) }}</div>
        <div><span class="label">Soma dos itens</span>{{ Formatar::moeda($somaItens) }}</div>
        <div class="conferencia-status">
            ⚠ Diferença de {{ Formatar::moeda(abs($somaItens - (float) $pedido->valor)) }} — confira com o PDF.
        </div>
    </div>
@endif
