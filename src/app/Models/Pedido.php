<?php

namespace App\Models;

use App\Helpers\UtilsNormalizarNumero;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pedido extends Model
{
    /** Blocos de endereço do cabeçalho do PDF (chave => título). */
    public const BLOCOS = [
        'fornecedor' => 'Fornecedor',
        'faturamento' => 'Faturamento',
        'local' => 'Local de entrega / prestação',
        'cobranca' => 'Endereço de cobrança',
    ];

    /** Condições do pedido guardadas em dados_extras (chave => rótulo). */
    public const CONDICOES = [
        'cond_pgto' => 'Condição de pagamento',
        'frete' => 'Frete',
        'moeda' => 'Moeda',
        'comprador' => 'Comprador',
        'contato_nome' => 'Contato',
        'contato_email' => 'E-mail',
        'total_icms' => 'ICMS total',
        'total_ipi' => 'IPI total',
        'total_produtos' => 'Total dos produtos',
    ];

    protected $fillable = ['numero', 'data_pedido', 'cliente', 'fornecedor', 'valor', 'texto_bruto', 'arquivo_pdf', 'dados_extras'];

    protected $casts = [
        'valor' => 'decimal:2',
        'data_pedido' => 'date',
        'dados_extras' => 'array',
    ];

    /** Pedidos do ano da planilha de controle: data do pedido (ou, sem ela, a data de importação). */
    public function scopeDoAno($query, int $ano)
    {
        $inicio = "$ano-01-01";
        $fim = "$ano-12-31";

        return $query->where(fn ($q) => $q
            ->whereBetween('data_pedido', [$inicio, $fim])
            ->orWhere(fn ($q) => $q->whereNull('data_pedido')->whereBetween('created_at', [$inicio.' 00:00:00', $fim.' 23:59:59'])));
    }

    public function scopeSearch($query, array $filtros = [])
    {
        // Lista: só as colunas da tabela (evita carregar o texto bruto do PDF) e a contagem de itens
        $query->select(['id', 'numero', 'data_pedido', 'cliente', 'fornecedor', 'valor'])
            ->withCount([
                'itens',
                'itens as itens_andamento_count' => fn ($q) => $q->where('status', 'andamento'),
                'itens as itens_finalizado_count' => fn ($q) => $q->where('status', 'finalizado'),
                'itens as itens_entregue_count' => fn ($q) => $q->where('status', 'entregue'),
                'itens as itens_cancelado_count' => fn ($q) => $q->where('status', 'cancelado'),
            ])
            // Entrega mais próxima entre os itens ainda em andamento (base da cor de prazo na lista)
            ->withMin(['itens as proxima_entrega' => fn ($q) => $q->where('status', 'andamento')], 'dt_entrega')
            ->withMax('itens as ultima_entrega', 'dt_entrega')
            ->orderBy('id', 'desc');

        if (! empty($filtros['numero']) && is_scalar($filtros['numero'])) {
            $query->where('numero', 'like', '%'.trim((string) $filtros['numero']).'%');
        }

        if (! empty($filtros['cliente']) && is_scalar($filtros['cliente'])) {
            $query->where('cliente', 'like', '%'.trim((string) $filtros['cliente']).'%');
        }

        if (! empty($filtros['fornecedor']) && is_scalar($filtros['fornecedor'])) {
            $query->where('fornecedor', 'like', '%'.trim((string) $filtros['fornecedor']).'%');
        }

        if (! empty($filtros['data_inicio'])) {
            $query->whereDate('data_pedido', '>=', $filtros['data_inicio']);
        }

        if (! empty($filtros['data_fim'])) {
            $query->whereDate('data_pedido', '<=', $filtros['data_fim']);
        }

        $valorMin = isset($filtros['valor_min']) && is_scalar($filtros['valor_min'])
            ? UtilsNormalizarNumero::normalizarNumero((string) $filtros['valor_min'])
            : null;

        if ($valorMin !== null) {
            $query->where('valor', '>=', $valorMin);
        }

        $valorMax = isset($filtros['valor_max']) && is_scalar($filtros['valor_max'])
            ? UtilsNormalizarNumero::normalizarNumero((string) $filtros['valor_max'])
            : null;

        if ($valorMax !== null) {
            $query->where('valor', '<=', $valorMax);
        }

        return $query;
    }

    /**
     * Situação do pedido a partir dos status dos itens: cancelado (todos cancelados), entregue (todos encerrados),
     * finalizado (nenhum em andamento) ou andamento.
     */
    public static function situacaoGeral(int $total, int $cancelados, int $entregues, int $andamento): string
    {
        return match (true) {
            $total === 0 => 'andamento',
            $cancelados === $total => 'cancelado',
            $cancelados + $entregues === $total => 'entregue',
            $andamento === 0 => 'finalizado',
            default => 'andamento',
        };
    }

    public function itens(): HasMany
    {
        return $this->hasMany(PedidoItem::class, 'pedido_id')->orderBy('id'); // ordem fixa: mudar o status/UPDATE não pode reordenar os itens
    }
}
