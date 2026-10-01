<?php

namespace App\Http\Resources;

use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Pedido para o front. Os campos pesados (itens, dados_extras, texto_bruto) só saem quando carregados,
 * então a lista continua leve.
 *
 * @mixin Pedido
 */
class PedidoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $atributos = $this->getAttributes();

        return [
            'id' => $this->id,
            'numero' => $this->numero,
            'data_pedido' => $this->data_pedido?->format('Y-m-d'),
            'cliente' => $this->cliente,
            'fornecedor' => $this->fornecedor,
            'valor' => $this->valor,
            'itens_count' => $this->whenCounted('itens'),
            'itens' => PedidoItemResource::collection($this->whenLoaded('itens')),
            'dados_extras' => $this->when(array_key_exists('dados_extras', $atributos), fn () => $this->dados_extras ?? (object) []),
            'texto_bruto' => $this->when(array_key_exists('texto_bruto', $atributos), $this->texto_bruto),
            'tem_pdf' => $this->when(array_key_exists('arquivo_pdf', $atributos), fn () => (bool) $this->arquivo_pdf),
        ];
    }
}
