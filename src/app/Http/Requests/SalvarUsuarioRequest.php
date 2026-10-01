<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Validation\Rule;

/** Criação e edição de usuários pelo administrador. Na edição, a senha é opcional (vazia mantém a atual). */
class SalvarUsuarioRequest extends BaseRequest
{
    public function rules(): array
    {
        /** @var User|null $usuario */
        $usuario = $this->route('usuario');

        return [
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario?->id)],
            'password' => [$usuario ? 'nullable' : 'required', 'string', 'min:8'],
            'is_admin' => 'boolean',
            'ativo' => 'boolean',
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'Nome', 'email' => 'E-mail', 'password' => 'Senha'];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Já existe um usuário com este e-mail.',
            'password.min' => 'A senha deve ter pelo menos :min caracteres.',
        ] + parent::messages();
    }
}
