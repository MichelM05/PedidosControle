{{-- Tela de detalhes do pedido. Recebe $pedido (com itens). Cada bloco é um partial em detalhes/. --}}
@use('App\Helpers\Formatar')
@php
    $extras = $pedido->dados_extras ?? [];
    $somaItens = (float) $pedido->itens->sum('vlr_tot');
@endphp

<div class="pedido-card">
    @include('pedidos.partials.detalhes.resumo')
    @include('pedidos.partials.detalhes.partes')
    @include('pedidos.partials.detalhes.condicoes')
    @include('pedidos.partials.detalhes.conferencia')

    <h3 class="secao-titulo">Itens</h3>
    @forelse($pedido->itens as $item)
        @if($loop->first)<div class="itens-grid">@endif
        @include('pedidos.partials.detalhes.item')
        @if($loop->last)</div>@endif
    @empty
        <div class="empty-state"><p class="empty-state-text">Nenhum item neste pedido.</p></div>
    @endforelse

    @if($pedido->texto_bruto)
        <details class="texto-bruto">
            <summary>Ver texto extraído do PDF</summary>
            <pre>{{ $pedido->texto_bruto }}</pre>
        </details>
    @endif
</div>

@include('pedidos.partials.editar-dados')
