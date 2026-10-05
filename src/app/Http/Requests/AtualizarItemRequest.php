<?php

namespace App\Http\Requests;

/** Edição de um único item (modal da tela do pedido): mesmas regras dos itens do formulário do pedido. */
class AtualizarItemRequest extends BaseRequest
{
    public function rules(): array
    {
        return $this->doItem((new SavePedidoRequest)->rules());
    }

    public function attributes(): array
    {
        return $this->doItem((new SavePedidoRequest)->attributes());
    }

    /** Pega as entradas "itens.*.campo" e devolve "campo" (sem o id, que vem da rota). */
    private function doItem(array $regras): array
    {
        $itens = [];
        foreach ($regras as $chave => $regra) {
            if (str_starts_with($chave, 'itens.*.') && $chave !== 'itens.*.id') {
                $itens[substr($chave, strlen('itens.*.'))] = $regra;
            }
        }

        return $itens;
    }
}
