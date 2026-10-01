<?php

namespace App\Http\Requests;

use App\Models\PedidoItem;
use Illuminate\Validation\Rule;

/** Edição de uma célula da grade de controle: { campo, valor }. */
class AtualizarItemControleRequest extends BaseRequest
{
    /** Campos editáveis na grade (os demais vêm do pedido). */
    public const CAMPOS = ['denominacao', 'qtd', 'dt_entrega', 'cidade_entrega', ...PedidoItem::CAMPOS_CONTROLE];

    public function rules(): array
    {
        $campo = $this->input('campo');

        return [
            'campo' => ['required', Rule::in(self::CAMPOS)],
            'valor' => match ($campo) {
                'qtd' => 'nullable|numeric',
                'dt_entrega' => 'nullable|date',
                'status' => ['required', Rule::in(array_keys(PedidoItem::STATUS))],
                default => 'nullable|string|max:255',
            },
        ];
    }

    public function attributes(): array
    {
        return ['valor' => 'valor', 'campo' => 'campo'];
    }
}
