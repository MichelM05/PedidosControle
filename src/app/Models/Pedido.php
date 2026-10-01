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

    /** Ano em que o pedido cai na planilha de controle. */
    public function anoDoControle(): int
    {
        return (int) ($this->data_pedido ?? $this->created_at ?? now())->format('Y');
    }

    public function scopeSearch($query, array $filtros = [])
    {
        // Lista: só as colunas da tabela (evita carregar o texto bruto do PDF) e a contagem de itens
        $query->select(['id', 'numero', 'data_pedido', 'cliente', 'fornecedor', 'valor'])
            ->withCount('itens')
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

    public function itens(): HasMany
    {
        return $this->hasMany(PedidoItem::class, 'pedido_id');
    }
}
