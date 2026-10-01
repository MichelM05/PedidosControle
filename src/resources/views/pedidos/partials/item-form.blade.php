{{-- Card de item do formulário. Recebe $index e $item (array). --}}
@php
    $dec = ['type' => 'number', 'step' => '0.0001'];
    $secoes = [
        'Identificação' => ['cols' => 4, 'campos' => [
            ['item', 'Item', 'placeholder' => '10'],
            ['material', 'Material', 'placeholder' => 'Cód. material'],
            ['denominacao', 'Denominação', 'span' => 2, 'placeholder' => 'Descrição'],
        ]],
        'Quantidade e valores' => ['cols' => 4, 'campos' => [
            ['qtd', 'Quantidade'] + $dec,
            ['un', 'Unidade', 'placeholder' => 'UN'],
            ['preco', 'Preço unit.'] + $dec,
            ['vlr_tot', 'Valor total'] + $dec,
        ]],
        'Impostos e ajustes' => ['cols' => 3, 'campos' => [
            ['icms', 'ICMS (%)'] + $dec,
            ['ipi', 'IPI (%)'] + $dec,
            ['icms_monofasico', 'ICMS monofásico'] + $dec,
            ['reducao_base_icms', 'Redução base ICMS'] + $dec,
            ['desconto_absoluto', 'Desconto absoluto'] + $dec,
            ['base_inss', 'Base cálculo INSS (%)'] + $dec,
        ]],
        'Serviço / entrega' => ['cols' => 3, 'campos' => [
            ['dt_entrega', 'Dt. entrega', 'type' => 'date'],
            ['local_prestacao', 'Local da prestação', 'span' => 2],
            ['tipo_manutencao', 'Tipo de manutenção', 'span' => 3],
            ['item_lei', 'Item lei', 'span' => 3],
        ]],
    ];
@endphp

<div class="item-form-card card">
    <div class="item-header">
        <div class="font-bold">Item #<span class="item-numero">{{ is_numeric($index) ? $index + 1 : '' }}</span></div>
        <div>
            <button type="button" class="btn-toggle-item" title="Minimizar/expandir">-</button>
            <button type="button" class="btn-remove-item" title="Remover item">&times;</button>
        </div>
    </div>

    <div class="item-body">
        @foreach($secoes as $titulo => $secao)
            <fieldset class="item-section">
                <legend>{{ $titulo }}</legend>
                <div class="item-section-grid cols-{{ $secao['cols'] }}">
                    @foreach($secao['campos'] as $campo)
                        @php
                            [$nome, $rotulo] = $campo;
                            $valor = $item[$nome] ?? '';
                            if ($nome === 'dt_entrega' && $valor !== '') {
                                $valor = substr((string) $valor, 0, 10);
                            }
                            $erro = is_numeric($index) ? $errors->first("itens.$index.$nome") : null;
                        @endphp
                        <div class="form-group {{ isset($campo['span']) ? 'span-' . $campo['span'] : '' }}">
                            <label class="field-label">{{ $rotulo }}</label>
                            <input type="{{ $campo['type'] ?? 'text' }}"
                                   @if(isset($campo['step'])) step="{{ $campo['step'] }}" @endif
                                   name="itens[{{ $index }}][{{ $nome }}]"
                                   class="form-control {{ $erro ? 'is-invalid' : '' }}"
                                   value="{{ $valor }}"
                                   @if(isset($campo['placeholder'])) placeholder="{{ $campo['placeholder'] }}" @endif>
                            @if($erro)<span class="field-error">{{ $erro }}</span>@endif
                        </div>
                    @endforeach
                </div>
            </fieldset>
        @endforeach
    </div>
</div>
