<?php

namespace App\Http\Requests;

class SearchPedidoRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'numero' => 'nullable|string|max:50',
            'cliente' => 'nullable|string|max:100',
            'fornecedor' => 'nullable|string|max:100',
            'data_inicio' => 'nullable|date',
            'data_fim' => 'nullable|date|after_or_equal:data_inicio',
            'valor_min' => 'nullable|string', // string: pode vir com vírgula ("1.000,00")
            'valor_max' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'data_fim.after_or_equal' => 'A data final deve ser maior ou igual à data inicial.',
            'data_inicio.date' => 'A data inicial deve ser uma data válida.',
            'data_fim.date' => 'A data final deve ser uma data válida.',
        ] + parent::messages();
    }
}
