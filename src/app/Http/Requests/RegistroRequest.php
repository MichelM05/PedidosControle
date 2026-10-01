<?php

namespace App\Http\Requests;

class RegistroRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'Nome', 'email' => 'E-mail', 'password' => 'Senha'];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Já existe uma conta com este e-mail.',
            'password.min' => 'A senha deve ter pelo menos :min caracteres.',
            'password.confirmed' => 'A confirmação da senha não confere.',
        ] + parent::messages();
    }
}
