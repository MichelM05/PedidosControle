<?php

namespace App\Http\Resources;

use App\Models\PedidoItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PedidoItem */
class PedidoItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            ...$this->resource->only(['id', 'item', 'material', 'denominacao', 'qtd', 'un', 'preco', 'vlr_tot', 'icms', 'ipi', ...PedidoItem::CAMPOS_EXTRAS, ...PedidoItem::CAMPOS_CONTROLE]),
            'dt_entrega' => $this->dt_entrega?->format('Y-m-d'),
        ];
    }
}
