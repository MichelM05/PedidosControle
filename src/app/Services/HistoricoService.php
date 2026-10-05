<?php

namespace App\Services;

use App\Models\Historico;
use App\Models\Pedido;
use App\Models\PedidoItem;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Registra o histórico de alterações (quem, quando, o quê) de pedidos e itens.
 * É chamado pelos observers (PedidoObserver, PedidoItemObserver) e pelos serviços que criam pedidos.
 */
class HistoricoService
{
    /** Campos do pedido que entram no histórico (chave => rótulo). */
    private const CAMPOS_PEDIDO = [
        'numero' => 'Número do pedido', 'data_pedido' => 'Data do pedido', 'cliente' => 'Cliente',
        'fornecedor' => 'Fornecedor', 'valor' => 'Valor total',
    ];

    /** Campos do item que entram no histórico (chave => rótulo). */
    private const CAMPOS_ITEM = [
        'item' => 'Item', 'material' => 'Material', 'denominacao' => 'Descrição', 'qtd' => 'Quantidade', 'un' => 'Unidade',
        'preco' => 'Preço unit.', 'vlr_tot' => 'Valor total', 'icms' => 'ICMS (%)', 'ipi' => 'IPI (%)',
        'dt_entrega' => 'Data de entrega', 'item_lei' => 'Item lei', 'tipo_manutencao' => 'Tipo de manutenção',
        'local_prestacao' => 'Local da prestação', 'desconto_absoluto' => 'Desconto absoluto', 'icms_monofasico' => 'ICMS monofásico',
        'reducao_base_icms' => 'Redução base ICMS', 'base_inss' => 'Base cálculo INSS (%)', 'cidade_entrega' => 'Cidade entrega', 'observacoes' => 'Observações', 'fabricante' => 'Fabricante',
        'responsavel' => 'Responsável', 'status' => 'Status',
    ];

    private ?string $lote = null;

    private ?string $origem = null;

    private bool $semItens = false;

    /** Identificador das alterações salvas juntas (um por requisição ou comando). */
    public function lote(): string
    {
        return $this->lote ??= (string) Str::uuid();
    }

    /** Começa um novo grupo de alterações (usado em comandos que processam vários pedidos). */
    public function novoLote(): void
    {
        $this->lote = null;
    }

    /** Executa o bloco indicando a origem das alterações (ex.: "Importado do PDF"). */
    public function comOrigem(string $origem, Closure $bloco): mixed
    {
        $anterior = $this->origem;
        $this->origem = $origem;

        try {
            return $bloco();
        } finally {
            $this->origem = $anterior;
        }
    }

    /** Executa o bloco sem registrar a criação de cada item (a criação do pedido já resume os itens). */
    public function semRegistrarItens(Closure $bloco): mixed
    {
        $anterior = $this->semItens;
        $this->semItens = true;

        try {
            return $bloco();
        } finally {
            $this->semItens = $anterior;
        }
    }

    /** Registra a criação de um pedido (chamado depois de criar os itens, para resumir quantos são). */
    public function pedidoCriado(Pedido $pedido, string $origem): void
    {
        $this->gravar($pedido, null, 'criou', null, null, $pedido->itens()->count().' item(ns)', $origem);
    }

    public function pedidoAlterado(Pedido $pedido): void
    {
        foreach ($pedido->getChanges() as $campo => $novo) {
            if ($campo === 'dados_extras') {
                $this->diffExtras($pedido, (array) $pedido->getOriginal('dados_extras'), (array) $pedido->dados_extras);
            } elseif (isset(self::CAMPOS_PEDIDO[$campo])) {
                $this->gravar($pedido, null, 'editou', self::CAMPOS_PEDIDO[$campo], $this->formatar($pedido->getOriginal($campo)), $this->formatar($pedido->$campo));
            }
        }
    }

    public function pedidoExcluido(Pedido $pedido): void
    {
        $this->gravar($pedido, null, 'excluiu', null, null, null);
    }

    public function itemCriado(PedidoItem $item): void
    {
        if (! $this->semItens) {
            $this->gravar($item->pedido, $item, 'criou', null, null, null);
        }
    }

    public function itemAlterado(PedidoItem $item): void
    {
        foreach ($item->getChanges() as $campo => $novo) {
            if (isset(self::CAMPOS_ITEM[$campo])) {
                $this->gravar($item->pedido, $item, 'editou', self::CAMPOS_ITEM[$campo], $this->formatar($item->getOriginal($campo), $campo), $this->formatar($item->$campo, $campo));
            } elseif (isset(PedidoItem::ETAPAS[$campo])) {
                $this->gravar($item->pedido, $item, 'editou', PedidoItem::ETAPAS[$campo], $this->formatar($item->getOriginal($campo)), $this->formatar($item->$campo));
            }
        }
    }

    public function itemExcluido(PedidoItem $item): void
    {
        $this->gravar($item->pedido, $item, 'excluiu', null, null, null);
    }

    /**
     * Agrupa registros por lote (uma requisição = um bloco), do mais recente para o mais antigo.
     *
     * @param  Collection<int, Historico>  $registros
     * @return list<array<string, mixed>>
     */
    public function agrupar(Collection $registros): array
    {
        return $registros->sortByDesc('id')->groupBy('lote')->map(function (Collection $grupo) {
            $primeiro = $grupo->sortBy('id')->first();

            return [
                'lote' => $primeiro->lote,
                'quando' => $primeiro->created_at?->toIso8601String(),
                'usuario' => $primeiro->usuario_nome,
                'pedido_id' => $primeiro->pedido_id,
                'pedido_numero' => $primeiro->pedido_numero,
                'origem' => $primeiro->origem,
                'registros' => $grupo->sortBy('id')->values()->map(fn (Historico $h) => $h->only([
                    'id', 'acao', 'campo', 'valor_anterior', 'valor_novo', 'item_id', 'item_descricao',
                ]))->all(),
            ];
        })->values()->all();
    }

    private function gravar(?Pedido $pedido, ?PedidoItem $item, string $acao, ?string $campo, ?string $anterior, ?string $novo, ?string $origem = null): void
    {
        if ($anterior === $novo && $acao === 'editou') {
            return; // nada mudou de verdade (ex.: "100" → "100.00")
        }

        $usuario = auth()->user();

        Historico::create([
            'lote' => $this->lote(),
            'user_id' => $usuario?->id,
            'usuario_nome' => $usuario?->name ?? 'Sistema',
            'pedido_id' => $pedido?->id,
            'pedido_numero' => $pedido?->numero,
            'item_id' => $item?->id,
            'item_descricao' => $item ? trim(($item->item ? "#{$item->item} " : '').($item->denominacao ?? '')) : null,
            'acao' => $acao,
            'campo' => $campo,
            'valor_anterior' => $anterior,
            'valor_novo' => $novo,
            'origem' => $origem ?? $this->origem,
        ]);
    }

    /** Compara o cabeçalho (condições, observações e blocos de endereço) campo a campo. */
    private function diffExtras(Pedido $pedido, array $antes, array $depois): void
    {
        $rotulos = Pedido::CONDICOES + ['observacoes' => 'Observações'];
        foreach ($rotulos as $chave => $rotulo) {
            $this->gravar($pedido, null, 'editou', $rotulo, $this->formatar($antes[$chave] ?? null), $this->formatar($depois[$chave] ?? null));
        }

        foreach (Pedido::BLOCOS as $chave => $titulo) {
            $this->gravar($pedido, null, 'editou', $titulo, $this->resumoBloco($antes['blocos'][$chave] ?? []), $this->resumoBloco($depois['blocos'][$chave] ?? []));
        }
    }

    private function resumoBloco(array $bloco): ?string
    {
        $texto = implode(' · ', array_filter([
            $bloco['nome'] ?? null,
            implode(', ', $bloco['endereco'] ?? []),
            ! empty($bloco['cnpj']) ? 'CNPJ '.$bloco['cnpj'] : null,
            ! empty($bloco['ie']) ? 'IE '.$bloco['ie'] : null,
            ! empty($bloco['fone']) ? 'Fone '.$bloco['fone'] : null,
        ]));

        return $texto !== '' ? $texto : null;
    }

    /** Valor em texto legível: datas dd/mm/aaaa, números sem zeros à direita, status pelo rótulo. */
    private function formatar(mixed $valor, ?string $campo = null): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }
        if ($valor instanceof CarbonInterface) {
            return $valor->format('d/m/Y');
        }
        if ($campo === 'status') {
            return PedidoItem::STATUS[$valor] ?? (string) $valor;
        }
        if (is_string($valor) && preg_match('/^\d{4}-\d{2}-\d{2}/', $valor)) {
            return substr($valor, 8, 2).'/'.substr($valor, 5, 2).'/'.substr($valor, 0, 4);
        }
        if (is_numeric($valor) && str_contains((string) $valor, '.')) {
            return rtrim(rtrim((string) $valor, '0'), '.');
        }

        return (string) $valor;
    }
}
