{{-- Modais de edição dos dados do pedido (sem os itens). Recebe $pedido. --}}
@use('App\Models\Pedido')
@php
    $extras = $pedido->dados_extras ?? [];
    $blocos = $extras['blocos'] ?? [];
    $abrir = session('abrir_modal');

    $secoes = [
        'resumo' => ['Dados do pedido', [
            ['numero', 'Número do pedido'],
            ['data_pedido', 'Data do pedido', 'date'],
            ['valor', 'Valor total (R$)', 'number', 'step' => '0.01'],
            ['cliente', 'Cliente'],
            ['fornecedor', 'Fornecedor'],
        ], [
            'numero' => $pedido->numero, 'data_pedido' => $pedido->data_pedido?->format('Y-m-d'), 'valor' => $pedido->valor,
            'cliente' => $pedido->cliente, 'fornecedor' => $pedido->fornecedor,
        ]],
        'condicoes' => ['Condições do pedido', collect(Pedido::CONDICOES)->map(fn ($rotulo, $chave) => match (true) {
            str_starts_with($chave, 'total_') => [$chave, $rotulo, 'number', 'step' => '0.01'],
            $chave === 'contato_email' => [$chave, $rotulo, 'email'],
            default => [$chave, $rotulo],
        })->values()->all(), $extras],
        'observacoes' => ['Observações', [
            ['observacoes', 'Observações', 'textarea'],
        ], $extras],
    ];
    foreach (Pedido::BLOCOS as $chave => $titulo) {
        $b = $blocos[$chave] ?? [];
        $b['nome'] = $b['nome'] ?? ['fornecedor' => $pedido->fornecedor, 'faturamento' => $pedido->cliente][$chave] ?? null;
        $b['endereco'] = implode("\n", $b['endereco'] ?? []);
        $secoes[$chave] = [$titulo, [
            ['nome', 'Nome'], ['endereco', 'Endereço (uma linha por linha)', 'textarea'],
            ['cnpj', 'CNPJ'], ['ie', 'IE'], ['fone', 'Fone'],
        ], $b];
    }
@endphp

@foreach($secoes as $secao => [$titulo, $lista, $valores])
    @php $aberto = $abrir === $secao; @endphp
    <dialog id="edit-{{ $secao }}" class="modal modal-form" @if($aberto) data-auto-open @endif>
        <form action="{{ route('pedidos.dados', $pedido) }}" method="POST" class="modal-body">
            @csrf
            @method('PATCH')
            <input type="hidden" name="secao" value="{{ $secao }}">

            <h3 class="modal-title">{{ $titulo }}</h3>

            <div class="modal-campos">
                @foreach($lista as $campo)
                    @php
                        [$nome, $rotulo] = $campo;
                        $tipo = $campo[2] ?? 'text';
                        $valor = $aberto ? old($nome) : ($valores[$nome] ?? '');
                        $erro = $aberto ? $errors->first($nome) : null;
                    @endphp
                    <div class="form-group {{ $tipo === 'textarea' ? 'full' : '' }}">
                        <label for="{{ $secao }}-{{ $nome }}" class="field-label">{{ $rotulo }}</label>
                        @if($tipo === 'textarea')
                            <textarea id="{{ $secao }}-{{ $nome }}" name="{{ $nome }}" rows="4"
                                      class="form-control {{ $erro ? 'is-invalid' : '' }}">{{ $valor }}</textarea>
                        @else
                            <input type="{{ $tipo }}" @if(isset($campo['step'])) step="{{ $campo['step'] }}" @endif
                                   id="{{ $secao }}-{{ $nome }}" name="{{ $nome }}" value="{{ $valor }}"
                                   class="form-control {{ $erro ? 'is-invalid' : '' }}">
                        @endif
                        @if($erro)<span class="field-error">{{ $erro }}</span>@endif
                    </div>
                @endforeach
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar</button>
            </div>
        </form>
    </dialog>
@endforeach
