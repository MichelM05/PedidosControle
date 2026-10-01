<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoItem extends Model
{
    protected $table = 'pedido_itens';

    /** Campos além de qtd/preço/impostos, extraídos das linhas abaixo de cada item no PDF. */
    public const CAMPOS_EXTRAS = [
        'dt_entrega', 'item_lei', 'tipo_manutencao', 'local_prestacao',
        'desconto_absoluto', 'icms_monofasico', 'reducao_base_icms', 'base_inss',
    ];

    protected $fillable = [
        'pedido_id', 'item', 'material', 'denominacao', 'qtd', 'un', 'preco', 'vlr_tot', 'icms', 'ipi',
        ...self::CAMPOS_EXTRAS,
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

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }
}
