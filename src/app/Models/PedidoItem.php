<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoItem extends Model
{
    protected $table = 'pedido_itens';

    /** Campos além de qtd/preço/impostos, extraídos das linhas abaixo de cada item no PDF. */
    public const CAMPOS_EXTRAS = [
        'dt_entrega', 'item_lei', 'tipo_manutencao', 'local_prestacao',
        'desconto_absoluto', 'icms_monofasico', 'reducao_base_icms', 'base_inss',
        'cidade_entrega', 'observacoes', 'fabricante',
    ];

    /** Etapas do controle de produção (chave => rótulo). Valor livre: texto ou data. */
    public const ETAPAS = [
        'desenho_nesting' => 'Desenho nesting',
        'compra_mp' => 'Compra M.P',
        'compra_insumo' => 'Compra insumo',
        'usinagem' => 'Usinagem',
        'corte_dobra' => 'Corte e/ou dobra',
        'solda' => 'Solda',
        'pintura' => 'Pintura',
        'montagem' => 'Montagem',
    ];

    /** Situação do item no controle (chave => rótulo). */
    public const STATUS = [
        'andamento' => 'Andamento',
        'finalizado' => 'Finalizado',
        'entregue' => 'Entregue',
        'cancelado' => 'Cancelado',
    ];

    /** Campos preenchidos pela equipe (não vêm do PDF): ficam fora do parser e do pedidos:reextrair. */
    public const CAMPOS_CONTROLE = [
        'desenho_nesting', 'compra_mp', 'compra_insumo', 'usinagem', 'corte_dobra', 'solda', 'pintura', 'montagem', // = ETAPAS
        'responsavel', 'status',
    ];

    protected $fillable = [
        'pedido_id', 'item', 'material', 'denominacao', 'qtd', 'un', 'preco', 'vlr_tot', 'icms', 'ipi',
        ...self::CAMPOS_EXTRAS,
        ...self::CAMPOS_CONTROLE,
    ];

    protected $casts = [
        'qtd' => 'decimal:4',
        'preco' => 'decimal:4',
        'vlr_tot' => 'decimal:4',
        'icms' => 'decimal:4',
        'ipi' => 'decimal:4',
        'dt_entrega' => 'date',
        'desconto_absoluto' => 'decimal:4',
        'icms_monofasico' => 'decimal:4',
        'reducao_base_icms' => 'decimal:4',
        'base_inss' => 'decimal:4',
    ];

    /** O fabricante é sempre guardado em caixa alta, qualquer que seja a origem (PDF, formulário ou modal). */
    protected function fabricante(): Attribute
    {
        return Attribute::set(fn (?string $valor) => $valor === null || trim($valor) === '' ? null : mb_strtoupper(trim($valor)));
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }
}
