<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class AtualizarPerfilRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user()->id)],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'Nome', 'email' => 'E-mail'];
    }

    public function messages(): array
    {
        return ['email.unique' => 'Já existe um usuário com este e-mail.'] + parent::messages();
    }
}
