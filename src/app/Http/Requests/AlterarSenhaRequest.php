<?php

namespace App\Http\Requests;

class AlterarSenhaRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'senha_atual' => 'required|string|current_password',
            'password' => ['required', 'string', 'confirmed', 'different:senha_atual', self::regraSenha()],
        ];
    }

    public function attributes(): array
    {
        return ['senha_atual' => 'Senha atual', 'password' => 'Nova senha'];
    }

    public function messages(): array
    {
        return [
            'senha_atual.current_password' => 'A senha atual está incorreta.',
            'password.min' => 'A nova senha deve ter pelo menos :min caracteres.',
            'password.letters' => 'A nova senha deve ter pelo menos uma letra.',
            'password.numbers' => 'A nova senha deve ter pelo menos um número.',
            'password.confirmed' => 'A confirmação da nova senha não confere.',
            'password.different' => 'A nova senha deve ser diferente da atual.',
        ] + parent::messages();
    }
}
