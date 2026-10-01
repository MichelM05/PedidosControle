<?php

namespace App\Http\Resources;

use App\Models\PedidoItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Uma linha da grade de controle: um item com os dados do pedido, nas chaves de ColunasControle.
 *
 * @mixin PedidoItem
 */
class ControleLinhaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pedido_id' => $this->pedido_id,
            'pedido' => null,
            'cliente' => $this->pedido?->cliente,
            'numero' => $this->pedido?->numero,
            'denominacao' => $this->denominacao,
            'qtd' => $this->qtd === null ? null : (float) $this->qtd,
            'dt_entrega' => $this->dt_entrega?->format('Y-m-d'),
            ...$this->resource->only(['cidade_entrega', ...PedidoItem::CAMPOS_CONTROLE]),
        ];
    }
}
