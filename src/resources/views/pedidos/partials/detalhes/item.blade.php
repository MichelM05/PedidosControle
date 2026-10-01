@use('App\Helpers\Formatar')
{{-- Card de um item do pedido. Recebe $item. --}}
<div class="item-card">
    <div class="item-card-topo">
        <div>
            <span class="item-badge">Item {{ $item->item ?? $loop->iteration }}</span>
            <div class="item-titulo">{{ Formatar::texto($item->denominacao) }}</div>
            @if($item->material && $item->material !== $item->denominacao)
                <div class="item-sub">Material: {{ $item->material }}</div>
            @endif
        </div>
        <div class="item-total">
            <span class="label">Valor total</span>
            {{ Formatar::moeda($item->vlr_tot) }}
            @if($item->qtd !== null && $item->preco !== null && $item->vlr_tot !== null && abs((float) $item->qtd * (float) $item->preco - (float) $item->vlr_tot) >= 0.01)
                <span class="item-aviso" title="Qtd × preço ≠ valor total">⚠ qtd × preço difere</span>
            @endif
        </div>
    </div>

    <div class="item-bloco">
        <div class="item-campo"><span class="label">Qtd.</span>{{ Formatar::quantidade($item->qtd) }}</div>
        <div class="item-campo"><span class="label">Un.</span>{{ Formatar::texto($item->un) }}</div>
        <div class="item-campo"><span class="label">Preço unit.</span>{{ Formatar::preco($item->preco) }}</div>
        <div class="item-campo"><span class="label">ICMS (%)</span>{{ Formatar::numero($item->icms) }}</div>
        <div class="item-campo"><span class="label">IPI (%)</span>{{ Formatar::numero($item->ipi) }}</div>
        <div class="item-campo"><span class="label">ICMS monofásico</span>{{ Formatar::numero($item->icms_monofasico) }}</div>
        <div class="item-campo"><span class="label">Redução base ICMS</span>{{ Formatar::numero($item->reducao_base_icms) }}</div>
        <div class="item-campo"><span class="label">Desconto absoluto</span>{{ Formatar::numero($item->desconto_absoluto) }}</div>
        <div class="item-campo"><span class="label">Base cálculo INSS (%)</span>{{ Formatar::numero($item->base_inss) }}</div>
    </div>

    <div class="item-detalhes">
        <div><span class="label">Dt. entrega</span>{{ Formatar::data($item->dt_entrega) }}</div>
        <div><span class="label">Local da prestação</span>{{ Formatar::texto($item->local_prestacao) }}</div>
        <div><span class="label">Tipo de manutenção</span>{{ Formatar::texto($item->tipo_manutencao) }}</div>
        <div><span class="label">Item lei</span>{{ Formatar::texto($item->item_lei) }}</div>
    </div>
</div>
