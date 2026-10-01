<?php

namespace App\Http\Requests;

use App\Models\PedidoItem;

class SavePedidoRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'numero' => 'required|string|max:255',
            'data_pedido' => 'nullable|date',
            'cliente' => 'nullable|string|max:255',
            'fornecedor' => 'nullable|string|max:255',
            'valor' => 'nullable|numeric',

            // Validação dos itens (array)
            'itens' => 'nullable|array',
            'itens.*.item' => 'nullable|string',
            'itens.*.material' => 'nullable|string',
            'itens.*.denominacao' => 'nullable|string',
            'itens.*.qtd' => 'nullable|numeric',
            'itens.*.un' => 'nullable|string|max:10',
            'itens.*.preco' => 'nullable|numeric',
            'itens.*.vlr_tot' => 'nullable|numeric',
            'itens.*.icms' => 'nullable|numeric',
            'itens.*.ipi' => 'nullable|numeric',
            'itens.*.dt_entrega' => 'nullable|date',
            'itens.*.item_lei' => 'nullable|string',
            'itens.*.tipo_manutencao' => 'nullable|string',
            'itens.*.local_prestacao' => 'nullable|string',
            'itens.*.desconto_absoluto' => 'nullable|numeric',
            'itens.*.icms_monofasico' => 'nullable|numeric',
            'itens.*.reducao_base_icms' => 'nullable|numeric',
            'itens.*.base_inss' => 'nullable|numeric',
            'itens.*.cidade_entrega' => 'nullable|string|max:255',
            'itens.*.responsavel' => 'nullable|string|max:255',
            'itens.*.status' => 'nullable|in:'.implode(',', array_keys(PedidoItem::STATUS)),
            ...collect(array_keys(PedidoItem::ETAPAS))->mapWithKeys(fn ($etapa) => ["itens.*.$etapa" => 'nullable|string|max:255'])->all(),
        ];
    }

    /**
     * Customizando as mensagens de erro (Opcional, como no attributeLabels do Yii2)
     */
    public function attributes(): array
    {
        return [
            'numero' => 'Número do pedido',
            'data_pedido' => 'Data do pedido',
            'cliente' => 'Cliente',
            'fornecedor' => 'Fornecedor',
            'valor' => 'Valor total',
            'itens.*.item' => 'Item',
            'itens.*.material' => 'Material',
            'itens.*.denominacao' => 'Denominação',
            'itens.*.qtd' => 'Quantidade',
            'itens.*.un' => 'Unidade',
            'itens.*.preco' => 'Preço unit.',
            'itens.*.vlr_tot' => 'Valor total do item',
            'itens.*.icms' => 'ICMS',
            'itens.*.ipi' => 'IPI',
            'itens.*.dt_entrega' => 'Dt. entrega',
            'itens.*.item_lei' => 'Item lei',
            'itens.*.tipo_manutencao' => 'Tipo de manutenção',
            'itens.*.local_prestacao' => 'Local da prestação',
            'itens.*.desconto_absoluto' => 'Desconto absoluto',
            'itens.*.icms_monofasico' => 'ICMS monofásico',
            'itens.*.reducao_base_icms' => 'Redução base ICMS',
            'itens.*.base_inss' => 'Base de cálculo INSS',
        ];
    }
}
