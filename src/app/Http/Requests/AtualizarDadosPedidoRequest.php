<?php

namespace App\Http\Requests;

use App\Models\Pedido;

/**
 * Edição por seção dos dados do pedido (tudo, exceto os itens), usada pelos modais da tela de detalhes.
 */
class AtualizarDadosPedidoRequest extends BaseRequest
{
    public function rules(): array
    {
        $secao = $this->input('secao');

        $porSecao = match (true) {
            $secao === 'resumo' => [
                'numero' => 'required|string|max:255',
                'data_pedido' => 'nullable|date',
                'valor' => 'nullable|numeric',
                'cliente' => 'nullable|string|max:255',
                'fornecedor' => 'nullable|string|max:255',
            ],
            $secao === 'condicoes' => [
                'frete' => 'nullable|string|max:255',
                'cond_pgto' => 'nullable|string|max:255',
                'moeda' => 'nullable|string|max:50',
                'comprador' => 'nullable|string|max:255',
                'contato_nome' => 'nullable|string|max:255',
                'contato_email' => 'nullable|email|max:255',
                'total_icms' => 'nullable|numeric',
                'total_ipi' => 'nullable|numeric',
                'total_produtos' => 'nullable|numeric',
            ],
            $secao === 'conferencia' => [
                'diferenca_aceita' => 'nullable|numeric',
            ],
            $secao === 'observacoes' => [
                'observacoes' => 'nullable|string|max:5000',
            ],
            array_key_exists($secao, Pedido::BLOCOS) => [
                'nome' => 'nullable|string|max:255',
                'endereco' => 'nullable|string|max:1000',
                'cnpj' => 'nullable|string|max:30',
                'ie' => 'nullable|string|max:50',
                'fone' => 'nullable|string|max:50',
            ],
            default => [],
        };

        return ['secao' => 'required|in:resumo,condicoes,observacoes,conferencia,'.implode(',', array_keys(Pedido::BLOCOS))] + $porSecao;
    }

    public function attributes(): array
    {
        return [
            'numero' => 'Número do pedido', 'data_pedido' => 'Data do pedido', 'valor' => 'Valor total',
            'cliente' => 'Cliente', 'fornecedor' => 'Fornecedor', 'frete' => 'Frete',
            'cond_pgto' => 'Condição de pagamento', 'moeda' => 'Moeda', 'comprador' => 'Comprador',
            'contato_nome' => 'Contato', 'contato_email' => 'E-mail', 'total_icms' => 'ICMS total',
            'total_ipi' => 'IPI total', 'total_produtos' => 'Total dos produtos', 'observacoes' => 'Observações', 'diferenca_aceita' => 'Diferença aceita',
            'nome' => 'Nome', 'endereco' => 'Endereço', 'cnpj' => 'CNPJ', 'ie' => 'IE', 'fone' => 'Fone',
        ];
    }
}
