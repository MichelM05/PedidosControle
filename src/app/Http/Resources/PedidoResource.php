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
            'status_geral' => $this->when(array_key_exists('itens_andamento_count', $atributos), fn () => $this->statusGeral()),
            'proxima_entrega' => $this->when(array_key_exists('proxima_entrega', $atributos), fn () => $this->proxima_entrega ? substr($this->proxima_entrega, 0, 10) : null),
            // Data de entrega exibida na lista: a mais próxima entre os itens em andamento; sem nenhum, a última entre todos
            'data_entrega' => $this->when(array_key_exists('ultima_entrega', $atributos), fn () => substr((string) ($this->proxima_entrega ?? $this->ultima_entrega), 0, 10) ?: null),
            'tem_pdf' => $this->when(array_key_exists('arquivo_pdf', $atributos), fn () => (bool) $this->arquivo_pdf),
        ];
    }

    /**
     * Situação do pedido a partir dos status dos itens: cancelado (todos cancelados), entregue (todos encerrados),
     * finalizado (nenhum em andamento) ou andamento.
     */
    private function statusGeral(): string
    {
        $total = (int) $this->itens_count;
        $cancelados = (int) $this->itens_cancelado_count;
        $entregues = (int) $this->itens_entregue_count;

        return match (true) {
            $total === 0 => 'andamento',
            $cancelados === $total => 'cancelado',
            $cancelados + $entregues === $total => 'entregue',
            (int) $this->itens_andamento_count === 0 => 'finalizado',
            default => 'andamento',
        };
    }
}
