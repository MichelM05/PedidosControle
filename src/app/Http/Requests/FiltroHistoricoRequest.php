<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class FiltroHistoricoRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'q' => 'nullable|string|max:100',
            'usuario' => 'nullable|integer',
            'acao' => ['nullable', Rule::in(['criou', 'editou', 'excluiu'])],
            'pedido' => 'nullable|integer',
            'de' => 'nullable|date',
            'ate' => 'nullable|date|after_or_equal:de',
        ];
    }

    public function messages(): array
    {
        return ['ate.after_or_equal' => 'A data final deve ser maior ou igual à data inicial.'] + parent::messages();
    }
}
