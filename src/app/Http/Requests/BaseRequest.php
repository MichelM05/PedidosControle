<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Base dos requests da aplicação: sem autenticação por enquanto e mensagens padrão em português.
 */
abstract class BaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [
            'required' => 'Informe o campo :attribute.',
            'numeric' => 'O campo :attribute deve ser um número.',
            'date' => 'O campo :attribute deve ser uma data válida.',
            'email' => 'Informe um e-mail válido.',
            'max' => 'O campo :attribute não pode ter mais de :max caracteres.',
            'string' => 'O campo :attribute é inválido.',
        ];
    }
}
