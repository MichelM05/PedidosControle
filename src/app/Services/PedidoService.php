<?php

namespace App\Services;

use App\Models\Pedido;
use Illuminate\Support\Facades\DB;

/**
 * Regras de gravação do pedido: formulário completo (com itens) e edição por seção.
 */
class PedidoService
{
    public function __construct(private HistoricoService $historico) {}

    /**
     * Cria ou atualiza o pedido e sincroniza os itens: itens com `id` do próprio pedido são atualizados (mantendo o id e o
     * histórico), os sem `id` são criados e os que não vieram são removidos. Aceita zero itens.
     *
     * @throws \Throwable
     */
    public function salvar(Pedido $pedido, array $dados, array $itens = []): Pedido
    {
        return DB::transaction(function () use ($pedido, $dados, $itens) {
            $novo = ! $pedido->exists;
            $pedido->fill($dados)->save();

            // Pedido novo: a criação do pedido já resume os itens, então não registra cada um
            $novo
                ? $this->historico->semRegistrarItens(fn () => $this->sincronizarItens($pedido, $itens))
                : $this->sincronizarItens($pedido, $itens);

            if ($novo) {
                $this->historico->pedidoCriado($pedido, 'Criado manualmente');
            }

            return $pedido;
        });
    }

    private function sincronizarItens(Pedido $pedido, array $itens): void
    {
        $existentes = $pedido->itens()->get()->keyBy('id');
        $mantidos = [];

        foreach ($itens as $dados) {
            $id = isset($dados['id']) ? (int) $dados['id'] : null;
            unset($dados['id']);
            // Item sem situação informada entra como "andamento" (padrão do controle)
            $dados['status'] = $dados['status'] ?? 'andamento';

            if ($id && $existentes->has($id)) {
                $existentes[$id]->update($dados);
                $mantidos[] = $id;
            } else {
                $pedido->itens()->create($dados);
            }
        }

        $existentes->except($mantidos)->each->delete();
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
