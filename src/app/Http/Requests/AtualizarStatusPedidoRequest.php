<?php

namespace App\Http\Requests;

use App\Models\PedidoItem;
use Illuminate\Validation\Rule;

class AtualizarStatusPedidoRequest extends BaseRequest
{
    public function rules(): array
    {
        return ['status' => ['required', Rule::in(array_keys(PedidoItem::STATUS))]];
    }

    public function attributes(): array
    {
        return ['status' => 'Status'];
    }
}
