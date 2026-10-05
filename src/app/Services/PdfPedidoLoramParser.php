<?php

namespace App\Services;

use App\Helpers\UtilsNormalizarNumero;
use App\Models\PedidoItem;

/**
 * Lê o texto de um PDF de pedido no modelo Loram ("PEDIDO DE COMPRA / DADOS CADASTRAIS LORAM").
 * Devolve a mesma estrutura do PdfPedidoParser.
 */
class PdfPedidoLoramParser
{
    public static function reconhece(string $texto): bool
    {
        return (bool) preg_match('/DADOS\s+CADASTRAIS\s+LORAM/iu', $texto);
    }

    /** @return array{pedido: array, itens: array, dados_extras: array} */
    public function extrair(string $texto): array
    {
        $pega = fn (string $regex): ?string => preg_match($regex, $texto, $m) && trim($m[1]) !== '' ? trim(preg_replace('/\s+/u', ' ', $m[1])) : null;
        $num = fn (?string $v): ?string => UtilsNormalizarNumero::normalizarNumero($v);

        $data = preg_match('/Emiss[ãa]o\s+(\d{2})\/(\d{2})\/(\d{2,4})/iu', $texto, $d)
            ? (strlen($d[3]) === 2 ? '20' : '').$d[3]."-{$d[2]}-{$d[1]}" : null;
        $filial = $pega('/FILIAL DE FATURAMENTO[ \t]+(.+)$/mu');
        $fornecedor = $pega('/NOME DO FORNECEDOR[ \t]+(.+)$/mu');
        $cidade = $pega('/^CIDADE[ \t]+(.+)$/mu');
        $enderecoFornecedor = $pega('/^ENDEREÇO[ \t]+(.+)$/mu');

        $dados = [
            'frete' => $pega('/^FRETE:[ \t]*(.+)$/mu'),
            'cond_pgto' => $pega('/CONDIÇÃO DE PAGAMENTO[ \t]+(.+)$/mu'),
            'comprador' => $pega('/^COMPRADOR[ \t]+(.+)$/mu'),
            'contato_email' => $pega('/EMAIL COMPRADOR[ \t]+(\S+@\S+)/mu'),
            'moeda' => $pega('/^MOEDA[ \t]+(\S.*)$/mu'),
            'total_icms' => $num($pega('/TOTAL ICMS\/ST:?[ \t]*([\d\.,]+)/u')),
            'total_ipi' => $num($pega('/TOTAL IPI:?[ \t]*([\d\.,]+)/u')),
            'total_produtos' => $num($pega('/TOTAL PRODUTOS[ \t]*([\d\.,]+)/u')),
            'blocos' => array_filter([
                'fornecedor' => $this->bloco($fornecedor, array_filter([$enderecoFornecedor, $cidade]), $pega('/^CNPJ[ \t]+([\d\.\/\-]+)/mu'), null),
                'faturamento' => $this->bloco($pega('/RAZÃO SOCIAL[ \t]+(.+)$/mu') ?? $filial, [], $pega('/CNPJ FATURAMENTO[ \t]+([\d\.\/\-]+)/u'), 'Faturamento'),
                'local' => $this->bloco($filial, array_filter([$pega('/END\. PARA ENTREGA[ \t]+(.+)$/mu')]), null, 'Local de entrega'),
            ]),
        ];

        return [
            'pedido' => [
                'numero' => $pega('/N[ºo°]\s+(\d{4}-\d+)/iu'),
                'data_pedido' => $data,
                'cliente' => $pega('/RAZÃO SOCIAL[ \t]+(.+)$/mu') ?? $filial,
                'fornecedor' => $fornecedor,
                'valor' => $num($pega('/TOTAL GERAL[ \t]*([\d\.,]+)/u')),
            ],
            'itens' => $this->itens($texto),
            'dados_extras' => array_filter($dados, fn ($v) => $v !== null && $v !== [] && $v !== ''),
        ];
    }

    private function bloco(?string $nome, array $endereco, ?string $cnpj, ?string $titulo): ?array
    {
        $bloco = array_filter(['titulo' => $titulo, 'nome' => $nome, 'endereco' => array_values($endereco), 'cnpj' => $cnpj]);

        return isset($bloco['nome']) || isset($bloco['endereco']) ? $bloco : null;
    }

    /**
     * Separa a descrição do PDF em título curto e complemento (fabricante, referência, "comprar conforme desenho"...).
     *
     * @return array{0: ?string, 1: ?string}
     */
    private function separarTitulo(?string $descricao): array
    {
        if ($descricao === null) {
            return [null, null];
        }
        $corte = null;
        if (preg_match('/;|\s(?:Fabricante|Refer[êe]ncia|Ref\.|Comprar\s+Conforme|Conforme\s+Desenho)\b/iu', $descricao, $m, PREG_OFFSET_CAPTURE) && $m[0][1] >= 8) {
            $corte = $m[0][1];
        } elseif (mb_strlen($descricao) > 80) {
            $corte = mb_strrpos(mb_substr($descricao, 0, 80), ' ') ?: 80;
            $corte = strlen(mb_substr($descricao, 0, $corte)); // posição em bytes, como o preg
        }
        if ($corte === null) {
            return [mb_strimwidth($descricao, 0, 255, '…'), null];
        }

        $titulo = trim(substr($descricao, 0, $corte), " ;,-");
        $resto = trim(substr($descricao, $corte), " ;,-");

        return [$titulo, $resto !== '' ? $resto : null];
    }

    private function itens(string $texto): array
    {
        $partes = preg_split('/^[ \t]*ITEM:\s*(\d+)[ \t]*$/mu', $texto, -1, PREG_SPLIT_DELIM_CAPTURE);
        $itens = [];
        for ($i = 1; $i + 1 < count($partes); $i += 2) {
            // O bloco acaba nos totais do pedido; remove o rodapé repetido a cada página
            $bloco = preg_split('/^[ \t]*TOTAL\s+PRODUTOS/mu', $partes[$i + 1])[0];
            $bloco = preg_replace('/^.*Documento emitido pelo.*$/mu', '', $bloco);
            $pega = fn (string $regex): ?string => preg_match($regex, $bloco, $m) && trim($m[1]) !== '' ? trim($m[1]) : null;

            $descricao = preg_match('/DESCRIÇÃO:\s*(.*?)\s*PREÇO UNIT:/su', $bloco, $m) ? trim(preg_replace('/\s+/u', ' ', $m[1])) : null;
            [$titulo, $obs] = $this->separarTitulo($descricao);
            [$fabricante, $obs] = PdfPedidoParser::separarFabricante($obs);
            $entrega = preg_match('/DATA DE ENTREGA\s+(\d{2})\/(\d{2})\/(\d{2,4})/u', $bloco, $d)
                ? (strlen($d[3]) === 2 ? '20' : '').$d[3]."-{$d[2]}-{$d[1]}" : null;
            $num = fn (?string $v): ?string => UtilsNormalizarNumero::normalizarNumero($v);

            $itens[] = [
                'item' => $partes[$i],
                'material' => $pega('/CÓD\.\s*\(PN LORAM\)\s*(\S+)/u'),
                'denominacao' => $titulo, // coluna varchar(255)
                'observacoes' => $obs,
                'qtd' => $num($pega('/QTD:\s*([\d\.,]+)/u')),
                'un' => null,
                'preco' => $num($pega('/PREÇO UNIT:\s*([\d\.,]+)/u')),
                'vlr_tot' => $num($pega('/VALOR TOTAL:\s*([\d\.,]+)/u')),
                'icms' => null,
                'ipi' => null,
            ] + array_merge(array_fill_keys(PedidoItem::CAMPOS_EXTRAS, null), ['dt_entrega' => $entrega, 'fabricante' => $fabricante]);
        }

        return $itens;
    }
}
