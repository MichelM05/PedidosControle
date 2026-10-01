<?php

namespace App\Support;

/**
 * Colunas da planilha CONTROLE DE PEDIDOS (aba do ano): ordem, títulos, larguras e cores.
 * É a única fonte para a exportação .xlsx e para a grade da tela de controle.
 */
class ColunasControle
{
    // Cores de cabeçalho por grupo (hexadecimal sem #)
    public const COR_BASE = 'E7E6E6';

    public const COR_COMPRAS = 'FEF2CB';

    public const COR_PRODUCAO = 'E2EFD9';

    public const COR_STATUS = 'D6DCE4';

    // Cores das linhas: fundo padrão e regras por situação / prazo
    public const COR_LINHA = 'ECECEC';

    public const COR_ENTREGUE = 'A9D18E';

    public const COR_FINALIZADO = 'BDD7EE';

    public const COR_CANCELADO = 'D9C7EA';

    public const COR_ALERTA = 'FFD965';

    public const COR_URGENTE = 'FF9999';

    /** Itens em aberto ficam amarelos quando faltam até este número de dias para a entrega... */
    public const DIAS_ALERTA = 20;

    /** ...e vermelhos quando faltam até estes dias (ou a data já passou). */
    public const DIAS_URGENTE = 10;

    /** Situações que encerram o item: saem das regras de prazo e ganham risco no texto. */
    public const STATUS_ENCERRADOS = ['entregue', 'cancelado'];

    /**
     * @return list<array{chave: string, titulo: string, tela: string, largura: float, cor: string, alinha: string, oculta: bool, tipo: string}>
     */
    public static function lista(): array
    {
        $c = fn (string $chave, string $titulo, string $tela, float $largura, string $cor, string $alinha = 'center', string $tipo = 'texto', bool $oculta = false) => compact('chave', 'titulo', 'tela', 'largura', 'cor', 'alinha', 'oculta', 'tipo');

        return [
            $c('pedido', 'PEDIDO', 'Pedido', 9.85546875, self::COR_BASE, oculta: true),
            $c('cliente', 'CLIENTE', 'Cliente', 10, self::COR_BASE, oculta: true),
            $c('numero', 'O.C CLIENTE', 'O.C Cliente', 19.42578125, self::COR_BASE),
            $c('denominacao', 'DESCRIÇÃO PRODUTO', 'Descrição produto', 34.7109375, self::COR_BASE, 'left'),
            $c('qtd', 'QUANT.', 'Quant.', 10.7109375, self::COR_BASE, tipo: 'numero'),
            $c('dt_entrega', "DATA \nDE \nENTREGA", 'Data de entrega', 17.85546875, self::COR_BASE, tipo: 'data'),
            $c('cidade_entrega', "CIDADE \nENTREGA", 'Cidade entrega', 14.28515625, self::COR_BASE),
            $c('desenho_nesting', "DESENHO\nNESTING", 'Desenho nesting', 12.42578125, self::COR_BASE, tipo: 'etapa'),
            $c('compra_mp', "COMPRA \nM.P", 'Compra M.P', 16.28515625, self::COR_COMPRAS, tipo: 'etapa'),
            $c('compra_insumo', "COMPRA \nINSUMO", 'Compra insumo', 12.42578125, self::COR_COMPRAS, tipo: 'etapa'),
            $c('usinagem', 'USINAGEM', 'Usinagem', 11, self::COR_PRODUCAO, tipo: 'etapa'),
            $c('corte_dobra', "CORTE\nE/OU\nDOBRA", 'Corte e/ou dobra', 10.7109375, self::COR_PRODUCAO, tipo: 'etapa'),
            $c('solda', 'SOLDA', 'Solda', 10.85546875, self::COR_PRODUCAO, tipo: 'etapa'),
            $c('pintura', 'PINTURA', 'Pintura', 10.85546875, self::COR_PRODUCAO, tipo: 'etapa'),
            $c('montagem', 'MONTAGEM', 'Montagem', 11.7109375, self::COR_PRODUCAO, tipo: 'etapa'),
            $c('responsavel', 'RESPONSÁVEL', 'Responsável', 11.140625, self::COR_PRODUCAO),
            $c('status', 'STATUS', 'Status', 12.140625, self::COR_STATUS, tipo: 'status'),
        ];
    }

    /** Cores das linhas por situação e prazo (hexadecimal sem #), no formato enviado ao front. */
    public static function cores(): array
    {
        return [
            'linha' => self::COR_LINHA,
            'entregue' => self::COR_ENTREGUE,
            'finalizado' => self::COR_FINALIZADO,
            'cancelado' => self::COR_CANCELADO,
            'alerta' => self::COR_ALERTA,
            'urgente' => self::COR_URGENTE,
        ];
    }

    /** Dias para a entrega que disparam o amarelo (alerta) e o vermelho (urgente). */
    public static function prazos(): array
    {
        return ['alerta' => self::DIAS_ALERTA, 'urgente' => self::DIAS_URGENTE];
    }
}
