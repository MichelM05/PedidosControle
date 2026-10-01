<?php

namespace App\Http\Requests;

use App\Models\PedidoItem;
use Illuminate\Validation\Rule;

/** Edição do controle de produção de um item (modal da tela do pedido). */
class AtualizarControleItemRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'dt_entrega' => 'nullable|date',
            'cidade_entrega' => 'nullable|string|max:255',
            'responsavel' => 'nullable|string|max:255',
            'status' => ['required', Rule::in(array_keys(PedidoItem::STATUS))],
            ...collect(array_keys(PedidoItem::ETAPAS))->mapWithKeys(fn ($etapa) => [$etapa => 'nullable|string|max:255'])->all(),
        ];
    }

    public function attributes(): array
    {
        return [
            'dt_entrega' => 'Data de entrega',
            'cidade_entrega' => 'Cidade entrega',
            'responsavel' => 'Responsável',
            'status' => 'Status',
            ...PedidoItem::ETAPAS,
        ];
    }
}
