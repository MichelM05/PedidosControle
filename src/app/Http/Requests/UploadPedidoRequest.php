<?php

namespace App\Http\Requests;

class UploadPedidoRequest extends BaseRequest
{
    public function rules(): array
    {
        return ['pdf' => 'required|file|mimes:pdf|max:10240'];
    }

    public function messages(): array
    {
        return [
            'pdf.required' => 'Selecione um arquivo PDF.',
            'pdf.mimes' => 'O arquivo deve estar em formato PDF.',
            'pdf.max' => 'O PDF deve ter no máximo 10 MB.',
        ];
    }
}
