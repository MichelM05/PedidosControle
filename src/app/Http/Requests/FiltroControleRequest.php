<?php

namespace App\Http\Requests;

use App\Models\PedidoItem;
use Illuminate\Validation\Rule;

class FiltroControleRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'ano' => 'nullable|integer|between:2000,2100',
            'q' => 'nullable|string|max:100',
            'status' => ['nullable', Rule::in(array_keys(PedidoItem::STATUS))],
            'responsavel' => 'nullable|string|max:100',
            'ocultar_entregues' => 'nullable|boolean',
        ];
    }
}
