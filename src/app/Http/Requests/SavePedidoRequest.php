<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SavePedidoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Por enquanto, vamos permitir que todos possam salvar
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'numero'       => 'required|string|max:255',
            'data_pedido'  => 'nullable|date',
            'cliente'      => 'nullable|string|max:255',
            'fornecedor'   => 'nullable|string|max:255',
            'valor'        => 'nullable|numeric',

            // Validação dos itens (array)
            'itens'             => 'nullable|array',
            'itens.*.item'      => 'nullable|string',
            'itens.*.material'  => 'nullable|string',
            'itens.*.denominacao'=> 'nullable|string',
            'itens.*.qtd'       => 'nullable|numeric',
            'itens.*.un'        => 'nullable|string|max:10',
            'itens.*.preco'     => 'nullable|numeric',
            'itens.*.vlr_tot'   => 'nullable|numeric',
            'itens.*.icms'      => 'nullable|numeric',
            'itens.*.ipi'       => 'nullable|numeric',
        ];
    }

    /**
     * Customizando as mensagens de erro (Opcional, como no attributeLabels do Yii2)
     */
    public function attributes(): array
    {
        return [
            'numero' => 'Número do Pedido',
            'data_pedido' => 'Data do Pedido',
            'cliente' => 'Cliente',
            'fornecedor' => 'Fornecedor',
            'valor' => 'Valor Total',
        ];
    }
}
