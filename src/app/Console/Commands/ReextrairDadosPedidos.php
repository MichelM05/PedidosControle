<?php

namespace App\Console\Commands;

use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Services\PdfPedidoParser;
use Illuminate\Console\Command;

class ReextrairDadosPedidos extends Command
{
    protected $signature = 'pedidos:reextrair {--ids=* : Limita aos IDs informados}';

    protected $description = 'Reextrai do texto bruto o cabeçalho e preenche os campos vazios de pedidos e itens já importados';

    public function handle(PdfPedidoParser $parser): int
    {
        $query = Pedido::whereNotNull('texto_bruto')->with('itens');
        if ($this->option('ids')) {
            $query->whereIn('id', $this->option('ids'));
        }

        $total = 0;
        $query->each(function (Pedido $pedido) use ($parser, &$total) {
            $extraido = $parser->extrair($pedido->texto_bruto);

            // Só preenche o que está vazio: nunca sobrescreve edições manuais
            $pedido->update(['dados_extras' => $extraido['dados_extras']] + $this->apenasVazios($pedido, $extraido['pedido']));

            foreach ($pedido->itens->sortBy('id')->values() as $i => $item) {
                $novos = $this->apenasVazios($item, array_intersect_key($extraido['itens'][$i] ?? [], array_flip(PedidoItem::CAMPOS_EXTRAS)));
                if ($novos) {
                    $item->update($novos);
                }
            }
            $total++;
        });

        $this->info("{$total} pedido(s) atualizado(s).");

        return self::SUCCESS;
    }

    /** Dos valores extraídos, só os que não são nulos e cujo campo ainda está vazio no modelo. */
    private function apenasVazios(object $modelo, array $valores): array
    {
        return array_filter($valores, fn ($valor, $campo) => $valor !== null && $modelo->$campo === null, ARRAY_FILTER_USE_BOTH);
    }
}
