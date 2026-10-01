@use('App\Helpers\Formatar')
{{-- Condições comerciais (frete, pagamento, contato, totais) e observações --}}
<div class="condicoes">
    <div class="condicoes-topo">
        <span class="label">Condições do pedido</span>
        <button type="button" class="btn-editar" data-open-modal="edit-condicoes">Editar</button>
    </div>
    @php $preenchidas = false; @endphp
    @foreach(\App\Models\Pedido::CONDICOES as $chave => $rotulo)
        @continue(!isset($extras[$chave]))
        @php $preenchidas = true; @endphp
        <div>
            <span class="label">{{ $rotulo }}</span>
            {{ str_starts_with($chave, 'total_') ? Formatar::moeda($extras[$chave]) : $extras[$chave] }}
        </div>
    @endforeach
    @unless($preenchidas)
        <span class="parte-doc">Sem informações</span>
    @endunless
</div>

<div class="observacoes">
    <div class="condicoes-topo">
        <span class="label">Observações</span>
        <button type="button" class="btn-editar" data-open-modal="edit-observacoes">Editar</button>
    </div>
    @if(!empty($extras['observacoes']))
        {!! nl2br(e($extras['observacoes'])) !!}
    @else
        <span class="parte-doc">Sem observações</span>
    @endif
</div>
