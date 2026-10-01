@use('App\Helpers\Formatar')
{{-- Fornecedor, faturamento, local e cobrança. Sem blocos importados (pedido manual), mostra só cliente/fornecedor. --}}
@php $blocos = $extras['blocos'] ?? []; @endphp

<div class="pedido-partes">
    @if($blocos)
        @foreach(\App\Models\Pedido::BLOCOS as $chave => $titulo)
            @php
                $b = $blocos[$chave] ?? [];
                // Sem bloco importado: usa o nome salvo no pedido (cliente = faturamento)
                $nomeBase = ['fornecedor' => $pedido->fornecedor, 'faturamento' => $pedido->cliente][$chave] ?? null;
                if (!$b && $nomeBase) { $b = ['nome' => $nomeBase]; }
            @endphp
            <div class="parte">
                <div class="parte-topo">
                    <span class="label">{{ $b['titulo'] ?? $titulo }}</span>
                    <button type="button" class="btn-editar" data-open-modal="edit-{{ $chave }}">Editar</button>
                </div>
                @if($b)
                    <strong class="parte-nome">{{ $b['nome'] ?? '—' }}</strong>
                    @foreach($b['endereco'] ?? [] as $linha)
                        <div>{{ $linha }}</div>
                    @endforeach
                    @if(!empty($b['cnpj']))<div class="parte-doc">CNPJ: {{ Formatar::cnpj($b['cnpj']) }}</div>@endif
                    @if(!empty($b['ie']))<div class="parte-doc">IE: {{ $b['ie'] }}</div>@endif
                    @if(!empty($b['fone']))<div class="parte-doc">Fone: {{ $b['fone'] }}</div>@endif
                @else
                    <span class="parte-doc">Sem informações</span>
                @endif
            </div>
        @endforeach
    @else
        <div class="parte"><span class="label">Cliente</span>{{ Formatar::texto($pedido->cliente) }}</div>
        <div class="parte"><span class="label">Fornecedor</span>{{ Formatar::texto($pedido->fornecedor) }}</div>
    @endif
</div>
