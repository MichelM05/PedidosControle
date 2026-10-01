<?php

namespace App\Http\Requests;

class LoginRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|string|email',
            'password' => 'required|string',
            'remember' => 'nullable|boolean',
        ];
    }

    public function attributes(): array
    {
        return ['email' => 'E-mail', 'password' => 'Senha'];
    }
}
