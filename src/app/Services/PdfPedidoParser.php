<?php

namespace App\Services;

use App\Helpers\UtilsNormalizarNumero;
use App\Models\PedidoItem;

/**
 * Lê o texto de um PDF de pedido (Rumo / SAP) e devolve os dados estruturados.
 * Veja docs/parser-pdf.md para os formatos suportados e como estender.
 */
class PdfPedidoParser
{
    /** Início de uma linha de valores do item: "1 UR 10.778,46 ..." */
    private const LINHA_VALORES = '/^[\d\.,]+\s+\p{L}{2,}\s+[\d\.,]/u';

    /**
     * Ponto de entrada: tudo que dá para extrair de um texto de PDF.
     *
     * @return array{pedido: array, itens: array, dados_extras: array}
     */
    public function extrair(string $texto): array
    {
        if (PdfPedidoLoramParser::reconhece($texto)) {
            return (new PdfPedidoLoramParser)->extrair($texto);
        }

        return [
            'pedido' => [
                'numero' => $this->extrairNumero($texto),
                'data_pedido' => $this->extrairData($texto),
                'cliente' => $this->extrairCliente($texto),
                'fornecedor' => $this->extrairFornecedor($texto),
                'valor' => $this->extrairValorTotal($texto),
            ],
            'itens' => $this->extrairItens($texto),
            'dados_extras' => $this->extrairDadosExtras($texto),
        ];
    }

    public function extrairNumero(string $texto): ?string
    {
        // Dois modelos: "Pedido de Compra nº123" e "Ped. Prest. Serv. nº123"
        preg_match('/(?:Pedido\s+de\s+Compra|Ped\.?\s*Prest\.?\s*Serv\.?)\s*n[ºo°]\s*(\d+)/iu', $texto, $m);

        return isset($m[1]) ? trim($m[1]) : null;
    }

    public function extrairData(string $texto): ?string
    {
        preg_match('/Data\s+do\s+pedido\s*:\s*(\d{2})\.(\d{2})\.(\d{4})/i', $texto, $d);

        return (isset($d[1]) && isset($d[2]) && isset($d[3])) ? "{$d[3]}-{$d[2]}-{$d[1]}" : null;
    }

    public function extrairCliente(string $texto): ?string
    {
        preg_match('/Cliente:\s*(.+?)(?:\n|$)/i', $texto, $cliente);
        if (empty($cliente[1])) {
            preg_match('/Faturamento\s*\n\s*(.+?)(?:\n|Rua|CNPJ)/is', $texto, $cliente);
        }

        return isset($cliente[1]) ? trim(preg_replace('/\s+/', ' ', $cliente[1])) : null;
    }

    public function extrairValorTotal(string $texto): ?string
    {
        preg_match('/Vlr\s+Total\s+do\s+Pedido\s*:\s*([\d\.,]+)/i', $texto, $valor);
        if (empty($valor[1])) {
            preg_match('/Total:\s*R?\$?\s*([\d\.,]+)/i', $texto, $valor);
        }

        return isset($valor[1]) ? UtilsNormalizarNumero::normalizarNumero($valor[1]) : null;
    }

    public function extrairFornecedor(string $texto): ?string
    {
        if (! preg_match('/Dados\s+do\s+(?:Fornecedor|Prestador)\s*\n+\s*([^\n]+)/i', $texto, $m)) {
            return null;
        }
        $linha = trim($m[1]);
        // Remover endereço se vier na mesma linha (RUA, número, bairro, CEP)
        if (preg_match('/^(.+?)\s+RUA\s+/i', $linha, $nome)) {
            return trim($nome[1]);
        }

        return $linha ?: null;
    }

    public function extrairItens(string $texto): array
    {
        $linhas = preg_split('/\r\n|\r|\n/', $texto);
        $headerEncontrado = false;
        $ultimaChave = null; // último metadado lido (para textos que quebram de linha)
        $itemLines = [];  // [{ item, material, denominacao }, ...]
        $valueLines = []; // [{ qtd, un, preco, vlr_tot, icms, ipi }, ...]

        foreach ($linhas as $linha) {
            $linha = trim($linha);
            if ($linha === '') {
                continue;
            }

            // Cabeçalho: "ItemMaterialDenominação" ou "Item Material Denominação"
            if (preg_match('/Item\s*Material\s*Denom/i', $linha)) {
                $headerEncontrado = true;

                continue;
            }

            if (! $headerEncontrado) {
                continue;
            }

            // Parar em totais
            if (preg_match('/^TOTAIS\s*:/i', $linha) || preg_match('/^Vlr\s+Total\s+do\s+Pedido/i', $linha)) {
                break;
            }

            // Metadados do item (aparecem entre a linha do item e a linha de valores)
            if ($itemLines !== [] && count($valueLines) < count($itemLines)) {
                $ultimo = count($itemLines) - 1;
                $meta = $this->extrairMetadadoItem($linha);
                if ($meta !== null) {
                    $itemLines[$ultimo] = array_merge($itemLines[$ultimo], $meta);
                    $ultimaChave = array_key_first($meta);

                    continue;
                }

                // "Item Lei" pode quebrar em várias linhas: anexa a continuação ao texto anterior
                if ($ultimaChave === 'item_lei'
                    && ! preg_match(self::LINHA_VALORES, $linha)
                    && ! preg_match('/^\d{4,}\s/', $linha)
                    && ! preg_match('/^==>/', $linha)) {
                    $itemLines[$ultimo]['item_lei'] .= ' '.$linha;

                    continue;
                }
                $ultimaChave = null;
            }

            // Linha de valores: "1 UR 10.778,46 10.778,46  0,00 %  0,00 %"
            if (preg_match('/^\s*([\d\.,]+)\s+(\p{L}{2,})\s+([\d\.,]+)\s+([\d\.,]+)(?:\s+([\d\.,]+)\s*%?)?(?:\s+([\d\.,]+)\s*%?)?/iu', $linha, $v)) {
                $valueLines[] = [
                    'qtd' => UtilsNormalizarNumero::normalizarNumero($v[1]),
                    'un' => $v[2],
                    'preco' => UtilsNormalizarNumero::normalizarNumero($v[3]),
                    'vlr_tot' => UtilsNormalizarNumero::normalizarNumero($v[4]),
                    'icms' => UtilsNormalizarNumero::normalizarNumero($v[5] ?? null),
                    'ipi' => UtilsNormalizarNumero::normalizarNumero($v[6] ?? null),
                ];

                continue;
            }

            // Linha de item + material/denom: "00010	SERV MANUT MAQUINAS E EQUP" ou "00010  SERV MANUT..."
            if (preg_match('/^(\d{4,})\s+(.+)$/', $linha, $m) && ! preg_match(self::LINHA_VALORES, $linha)) {
                $resto = trim($m[2]);
                if (strlen($resto) > 2 && ! preg_match('/^(Total|Subtotal|Dt\.|Tipo\s+de|Local\s+da|Base\s+de)/i', $resto)) {
                    $itemLines[] = [
                        'item' => $m[1],
                        'material' => $resto,
                        'denominacao' => $resto,
                    ];
                }
            }
        }

        // Montar itens: emparelhar por ordem (cada value line = 1 item; usar item line correspondente se houver)
        $itens = [];
        foreach ($valueLines as $i => $val) {
            $itemLine = $itemLines[$i] ?? null;
            $itens[] = [
                'item' => $itemLine['item'] ?? (string) ($i + 1),
                'material' => $itemLine['material'] ?? null,
                'denominacao' => $itemLine['denominacao'] ?? null,
                'qtd' => $val['qtd'],
                'un' => $val['un'],
                'preco' => $val['preco'],
                'vlr_tot' => $val['vlr_tot'],
                'icms' => $val['icms'],
                'ipi' => $val['ipi'],
            ] + array_merge(array_fill_keys(PedidoItem::CAMPOS_EXTRAS, null), array_intersect_key($itemLine ?? [], array_flip(PedidoItem::CAMPOS_EXTRAS)));
        }

        foreach ($itens as &$item) {
            $item['cidade_entrega'] ??= $this->cidadeSemUf($item['local_prestacao'] ?? null);
        }
        unset($item);

        // Fallback: se não achou linhas de valor, tentar parsing por colunas (espaços múltiplos)
        if (empty($itens)) {
            foreach ($linhas as $linha) {
                $linha = trim($linha);
                if (preg_match('/Item\s*Material\s*Denom/i', $linha)) {
                    continue;
                }
                if (preg_match('/^TOTAIS|^Vlr\s+Total/i', $linha)) {
                    break;
                }
                $row = $this->parsearLinhaItemColunas($linha);
                if ($row !== null) {
                    $itens[] = $row;
                }
            }
        }

        return $itens;
    }

    /**
     * Parseia linha com colunas separadas por 2+ espaços (formato alternativo).
     */
    private function parsearLinhaItemColunas(string $linha): ?array
    {
        $partes = preg_split('/\s{2,}/', $linha, -1, PREG_SPLIT_NO_EMPTY);
        $n = count($partes);
        if ($n < 7) {
            return null;
        }
        $qtd = UtilsNormalizarNumero::normalizarNumero($partes[$n - 6] ?? null);
        $un = $partes[$n - 5] ?? null;
        $preco = UtilsNormalizarNumero::normalizarNumero($partes[$n - 4] ?? null);
        $vlrTot = UtilsNormalizarNumero::normalizarNumero($partes[$n - 3] ?? null);
        $icms = UtilsNormalizarNumero::normalizarNumero($partes[$n - 2] ?? null);
        $ipi = UtilsNormalizarNumero::normalizarNumero($partes[$n - 1] ?? null);
        $denomPartes = array_slice($partes, 2, $n - 6);
        $denominacao = $denomPartes !== [] ? implode(' ', $denomPartes) : null;

        return [
            'item' => $partes[0] ?? null,
            'material' => $partes[1] ?? null,
            'denominacao' => $denominacao ?: null,
            'qtd' => $qtd,
            'un' => $un,
            'preco' => $preco,
            'vlr_tot' => $vlrTot,
            'icms' => $icms,
            'ipi' => $ipi,
        ];
    }

    /**
     * Reconhece linhas de metadados de um item (entrega, lei, manutenção, local e valores "==>").
     */
    private function extrairMetadadoItem(string $linha): ?array
    {
        if (preg_match('/^Dt\.\s*Entrega\s*:?\s*(\d{2})\.(\d{2})\.(\d{4})/i', $linha, $m)) {
            return ['dt_entrega' => "{$m[3]}-{$m[2]}-{$m[1]}"];
        }
        if (preg_match('/^Item\s+Lei\s*:\s*(.+)$/i', $linha, $m)) {
            return ['item_lei' => trim($m[1])];
        }
        if (preg_match('/^Tipo\s+de\s+Manuten\S*\s*:\s*(.*)$/iu', $linha, $m)) {
            return ['tipo_manutencao' => trim($m[1]) ?: null];
        }
        if (preg_match('/^Local\s+da\s+Presta\S*\s*:\s*(.*)$/iu', $linha, $m)) {
            return ['local_prestacao' => trim($m[1]) ?: null];
        }
        if (preg_match('/^Base\s+de\s+C[áa]lculo\s+INSS\s*:\s*([\d\.,]+)\s*%?/iu', $linha, $m)) {
            return ['base_inss' => str_contains($m[1], ',') ? UtilsNormalizarNumero::normalizarNumero($m[1]) : $m[1]];
        }
        if (preg_match('/^==>\s*([\d\.,]+)\s*\(\s*(Desconto\s+absoluto|ICMS\s+Monof\S*|Redu\S*\s+base\s+ICMS)\s*\)/iu', $linha, $m)) {
            // Nestas linhas o SAP usa ponto como separador decimal (ex.: "0.00")
            $valor = str_contains($m[1], ',') ? UtilsNormalizarNumero::normalizarNumero($m[1]) : $m[1];

            return match (true) {
                stripos($m[2], 'Desconto') === 0 => ['desconto_absoluto' => $valor],
                stripos($m[2], 'ICMS') === 0 => ['icms_monofasico' => $valor],
                default => ['reducao_base_icms' => $valor],
            };
        }

        return null;
    }

    /**
     * Extrai o cabeçalho do pedido: condições comerciais, contato, totais, observações e os
     * blocos de endereço (fornecedor/prestador, faturamento, cobrança e local de entrega/prestação).
     */
    public function extrairDadosExtras(string $texto): array
    {
        $pega = fn (string $regex): ?string => preg_match($regex, $texto, $m) && trim($m[1]) !== '' ? trim($m[1]) : null;
        $numero = fn (?string $v): ?string => $v === null ? null : UtilsNormalizarNumero::normalizarNumero($v);

        $contatoNome = $pega('/^Email:[ \t]*([^\n<@]*)$/mu');
        $email = preg_match('/Comprador\/Tel:.*?([\w.\-]+@[\w.\-]+\.[A-Za-z]{2,})/isu', $texto, $m) ? $m[1] : null;

        $dados = [
            'frete' => $pega('/^Frete:[ \t]*(.*)$/mu'),
            'cond_pgto' => $pega('/Cond\.?\s*Pgto:[ \t]*(.+)$/mu'),
            'comprador' => $pega('/Comprador\/Tel:[ \t]*(.+)$/mu'),
            'contato_nome' => $contatoNome,
            'contato_email' => $email,
            'moeda' => $pega('/^Moeda:[ \t]*(.+)$/mu'),
            'total_icms' => $numero($pega('/^ICMS:[ \t]*([\d\.,]+)/mu')),
            'total_ipi' => $numero($pega('/^IPI:[ \t]*([\d\.,]+)/mu')),
            'total_produtos' => $numero($pega('/Vlr\s+Total\s+dos\s+Produtos:[ \t]*([\d\.,]+)/iu')),
            'observacoes' => $this->extrairObservacoes($texto),
            'blocos' => array_filter([
                'fornecedor' => $this->extrairBloco($texto, '/^Dados\s+do\s+(?:Fornecedor|Prestador)\s*$/iu', 'Fornecedor'),
                'faturamento' => $this->extrairBloco($texto, '/^Faturamento\s*$/iu', 'Faturamento'),
                'cobranca' => $this->extrairBloco($texto, '/^Endere[çc]o\s+de\s+cobran[çc]a\s*$/iu', 'Endereço de cobrança'),
                'local' => $this->extrairBloco($texto, '/^(?:Entrega|Local\s+da\s+Presta[çc][ãa]o\s+de\s+Servi[çc]os)\s*:\s*$/iu', null),
            ]),
        ];

        // Título do bloco de local conforme o rótulo do PDF
        if (isset($dados['blocos']['local'])) {
            $dados['blocos']['local']['titulo'] = preg_match('/^Entrega\s*:\s*$/miu', $texto) ? 'Local de entrega' : 'Local da prestação de serviços';
        }

        return array_filter($dados, fn ($v) => $v !== null && $v !== [] && $v !== '');
    }

    private const REGEX_TITULO_BLOCO = '/^(?:Entrega\s*:|Local\s+da\s+Presta[çc][ãa]o\s+de\s+Servi[çc]os\s*:|Endere[çc]o\s+de\s+cobran[çc]a|Faturamento|Dados\s+do\s+(?:Fornecedor|Prestador)|(?:Pedido\s+de\s+Compra|Ped\.?\s*Prest\.?\s*Serv\.?)\s*n|Data\s+do\s+pedido|Detalhes\s+do\s+Pedido)/iu';

    private function extrairBloco(string $texto, string $regexTitulo, ?string $titulo): ?array
    {
        $linhas = array_map('trim', preg_split('/\r\n|\r|\n/', $texto));
        $inicio = null;
        foreach ($linhas as $i => $linha) {
            if (preg_match($regexTitulo, $linha)) {
                $inicio = $i + 1;
                break;
            }
        }
        if ($inicio === null) {
            return null;
        }

        $bloco = ['titulo' => $titulo, 'nome' => null, 'endereco' => []];
        for ($i = $inicio; $i < count($linhas); $i++) {
            $linha = $linhas[$i];
            if ($linha === '') {
                continue;
            }
            if (preg_match(self::REGEX_TITULO_BLOCO, $linha)) {
                break;
            }
            if (preg_match('/^CNPJ\s*:\s*(.*)$/iu', $linha, $m)) {
                $bloco['cnpj'] = trim($m[1]) ?: null;
            } elseif (preg_match('/^IE\s*:\s*(.*)$/iu', $linha, $m)) {
                $bloco['ie'] = trim($m[1]) ?: null;
            } elseif (preg_match('/^Fone\s*:\s*(.*)$/iu', $linha, $m)) {
                $bloco['fone'] = trim($m[1]) ?: null;
            } elseif (preg_match('/^Fax\s*:\s*(.*)$/iu', $linha, $m)) {
                continue;
            } elseif ($bloco['nome'] === null) {
                $bloco['nome'] = $linha;
            } else {
                $bloco['endereco'][] = $linha;
            }
        }

        return array_filter($bloco, fn ($v) => $v !== null && $v !== []);
    }

    /**
     * Observações do pedido: linhas entre os totais e o texto contratual padrão.
     */
    private function extrairObservacoes(string $texto): ?string
    {
        if (! preg_match('/Vlr\s+Total\s+dos\s+Produtos:[^\n]*\n(.*?)(?=^[ \t]*Pedido\s+de\s+Presta[çc][ãa]o\s+de\s+Servi[çc]os|\z)/imsu', $texto, $m)) {
            return null;
        }
        $linhas = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $m[1])), fn ($l) => $l !== '');

        return $linhas ? implode("\n", $linhas) : null;
    }

    /** "Ponta Grossa PR" → "Ponta Grossa" (a planilha de controle usa só a cidade). */
    private function cidadeSemUf(?string $local): ?string
    {
        $cidade = trim(preg_replace('/\s+[A-Z]{2}$/u', '', trim((string) $local)));

        return $cidade !== '' ? $cidade : null;
    }
}
