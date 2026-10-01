<?php

namespace App\Services;

use App\Models\Pedido;
use Illuminate\Support\Facades\DB;

/**
 * Regras de gravação do pedido: formulário completo (com itens) e edição por seção.
 */
class PedidoService
{
    /**
     * Cria ou atualiza o pedido e sincroniza os itens (os antigos são substituídos pelos enviados).
     *
     * @throws \Throwable
     */
    public function salvar(Pedido $pedido, array $dados, array $itens = []): Pedido
    {
        return DB::transaction(function () use ($pedido, $dados, $itens) {
            $pedido->fill($dados)->save();

            $pedido->itens()->delete();
            $pedido->itens()->createMany($itens);

            return $pedido;
        });
    }

    /**
     * Edita uma seção dos dados do pedido sem tocar nos itens.
     * Seções: resumo, condicoes, observacoes ou uma chave de Pedido::BLOCOS.
     */
    public function atualizarSecao(Pedido $pedido, string $secao, array $dados): void
    {
        $extras = $pedido->dados_extras ?? [];

        if ($secao === 'resumo') {
            $pedido->fill($dados);
            // Cliente e fornecedor também aparecem como nome dos blocos: mantém os dois coerentes
            $this->definirNomeBloco($extras, 'fornecedor', $dados['fornecedor'] ?? null);
            $this->definirNomeBloco($extras, 'faturamento', $dados['cliente'] ?? null);
        } elseif ($secao === 'condicoes') {
            $extras = [...array_diff_key($extras, $dados), ...$this->semVazios($dados)];
        } elseif ($secao === 'observacoes') {
            unset($extras['observacoes']);
            $extras += $this->semVazios($dados);
        } else {
            $dados['endereco'] = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) ($dados['endereco'] ?? '')))));
            $dados['titulo'] = $extras['blocos'][$secao]['titulo'] ?? Pedido::BLOCOS[$secao];
            $extras['blocos'][$secao] = $this->semVazios($dados);

            if ($secao === 'fornecedor') {
                $pedido->fornecedor = $dados['nome'] ?? null;
            } elseif ($secao === 'faturamento') {
                $pedido->cliente = $dados['nome'] ?? null;
            }
        }

        $pedido->dados_extras = $extras ?: null;
        $pedido->save();
    }

    private function definirNomeBloco(array &$extras, string $bloco, ?string $nome): void
    {
        if (isset($extras['blocos'][$bloco])) {
            $extras['blocos'][$bloco]['nome'] = $nome;
        }
    }

    private function semVazios(array $dados): array
    {
        return array_filter($dados, fn ($v) => $v !== null && $v !== '' && $v !== []);
    }
}
