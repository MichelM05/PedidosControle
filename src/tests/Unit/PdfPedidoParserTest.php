<?php

namespace Tests\Unit;

use App\Services\PdfPedidoParser;
use PHPUnit\Framework\TestCase;

class PdfPedidoParserTest extends TestCase
{
    private PdfPedidoParser $parser;

    protected function setUp(): void
    {
        $this->parser = new PdfPedidoParser;
    }

    private function texto(): string
    {
        return <<<'TXT'
Pedido de Compra nº4502006271
Data do pedido:11.02.2026
Detalhes do Pedido de Compra
ItemMaterialDenominação	Qtd. Un. Preço Vlr Tot. ICMS IPI
00010	SERV MANUT MAQUINAS E EQUP
Dt. Entrega 13.03.2026
Item Lei: 14.01  Lubrificação, limpeza, lustração, revisão
blindagem, manutenção e conserva
Tipo de Manutenção: CO CORRETIVA:QUEBRA/FALHA/CONSERTO DO EQUIPAMENTO
Local da Prestação: Ponta Grossa PR
Base de Cálculo INSS: 100.00%
==> 1.50  ( Desconto absoluto )
==> 2.00  ( ICMS Monofásico )
==> 3.25  ( Redução base ICMS )
1 UR 10.778,46 10.778,46     0,00 %     0,00 %
TOTAIS:
Vlr Total do Pedido: 10.778,46
TXT;
    }

    public function test_extrai_numero_e_data(): void
    {
        $this->assertSame('4502006271', $this->parser->extrairNumero($this->texto()));
        $this->assertSame('2026-02-11', $this->parser->extrairData($this->texto()));
    }

    public function test_extrai_item_com_campos_extras(): void
    {
        $itens = $this->parser->extrairItens($this->texto());

        $this->assertCount(1, $itens);
        $this->assertSame('00010', $itens[0]['item']);
        $this->assertSame('2026-03-13', $itens[0]['dt_entrega']);
        $this->assertStringStartsWith('14.01', $itens[0]['item_lei']);
        $this->assertStringEndsWith('blindagem, manutenção e conserva', $itens[0]['item_lei']);
        $this->assertStringStartsWith('CO CORRETIVA', $itens[0]['tipo_manutencao']);
        $this->assertSame('Ponta Grossa PR', $itens[0]['local_prestacao']);
        $this->assertEquals(1.5, $itens[0]['desconto_absoluto']);
        $this->assertEquals(2.0, $itens[0]['icms_monofasico']);
        $this->assertEquals(3.25, $itens[0]['reducao_base_icms']);
    }

    public function test_extrai_numero_e_fornecedor_do_modelo_prestacao_de_servico(): void
    {
        $texto = "Dados do Prestador\nA C BOSLOOPER IMPORTACAO E EXP\nRUA NUNES MACHADO 472 C 472, C\nPed. Prest. Serv. nº4501987929\nData do pedido: 02.12.2025\n";

        $this->assertSame('4501987929', $this->parser->extrairNumero($texto));
        $this->assertSame('A C BOSLOOPER IMPORTACAO E EXP', $this->parser->extrairFornecedor($texto));
    }

    public function test_extrai_cabecalho_com_blocos_e_totais(): void
    {
        $texto = <<<'TXT'
Local da Prestação de Serviços:
América Latina Logística S/A
Av. Maria Antonia Camargo de
14801-260 ARARAQUARA SP
CNPJ: 02.502.844/0001-66
IE: 149.569.373.118
Faturamento
Rumo Malha Paulista S.A
CNPJ: 02.502.844/0001-66
Ped. Prest. Serv. nº4501987929
Data do pedido:02.12.2025
Frete: CIF
Cond. Pgto: Pagamento em 30 dias
Pessoa de contato/telefone
Comprador/Tel:Serviços de TO
Email:
christian.benitez@rumolog.com
Dados do Prestador
A C BOSLOOPER IMPORTACAO E EXP
80250-000 CURITIBA PR
Fone: 4130854590
Fax:
CNPJ: 11866957000131
Detalhes do Pedido de Compra
TOTAIS:
Moeda: Real
ICMS:   0,00
IPI:                0,00
Vlr Total do Pedido:    984.000,00
Vlr Total dos Produtos: 984.000,00
Favor agendar entrega com:
Fulano
Pedido de Prestação de Serviços / Compra
1. REGRAS
TXT;

        $e = $this->parser->extrairDadosExtras($texto);

        $this->assertSame('CIF', $e['frete']);
        $this->assertSame('Pagamento em 30 dias', $e['cond_pgto']);
        $this->assertSame('Serviços de TO', $e['comprador']);
        $this->assertSame('christian.benitez@rumolog.com', $e['contato_email']);
        $this->assertSame('Real', $e['moeda']);
        $this->assertEquals(984000.00, $e['total_produtos']);
        $this->assertSame("Favor agendar entrega com:\nFulano", $e['observacoes']);
        $this->assertSame('A C BOSLOOPER IMPORTACAO E EXP', $e['blocos']['fornecedor']['nome']);
        $this->assertSame('11866957000131', $e['blocos']['fornecedor']['cnpj']);
        $this->assertSame('4130854590', $e['blocos']['fornecedor']['fone']);
        $this->assertSame('Local da prestação de serviços', $e['blocos']['local']['titulo']);
        $this->assertSame('149.569.373.118', $e['blocos']['local']['ie']);
        $this->assertSame('Rumo Malha Paulista S.A', $e['blocos']['faturamento']['nome']);
    }

    public function test_sem_observacoes_quando_nao_ha_texto_entre_totais_e_clausulas(): void
    {
        $texto = "Vlr Total dos Produtos: 10,00\n\n\nPedido de Prestação de Serviços / Compra\n1. REGRAS";

        $this->assertArrayNotHasKey('observacoes', $this->parser->extrairDadosExtras($texto));
    }

    public function test_cidade_de_entrega_vem_do_local_da_prestacao_sem_a_uf(): void
    {
        $texto = "ItemMaterialDenominação\tQtd. Un. Preço Vlr Tot. ICMS IPI\n00010\tSERV X\nLocal da Prestação: Ponta Grossa PR\n1 UR 10,00 10,00 0,00 % 0,00 %\nTOTAIS:\n";

        $this->assertSame('Ponta Grossa', $this->parser->extrairItens($texto)[0]['cidade_entrega']);
    }

    public function test_le_o_modelo_loram_com_itens_em_blocos(): void
    {
        $texto = <<<'T'
Emissão 04/08/26
PEDIDO DE COMPRA
Nº  0826-000012
DADOS CADASTRAIS LORAM:
RAZÃO SOCIAL LORAM DO BRASIL LTDA
FILIAL DE FATURAMENTO Loram do Brasil LTDA MG
CNPJ FATURAMENTO 20.245.901/0002-31
COMPRADOR Daniela Gora
DADOS CADASTRAIS FORNECEDOR:
NOME DO FORNECEDOR Connectrail LTDA
CONDIÇÃO DE PAGAMENTO 45DDL
FRETE:                                     Remetente
ITEM:1
CÓD. (PN LORAM)163756
QTD: 4,0000
DESCRIÇÃO: Suporte De Fixação Do Deck
PREÇO UNIT: 156,50
IPI: 0,00
VALOR TOTAL: 626,00
DESCRIÇÃO COMPLEMENTAR
DATA DE ENTREGA  11/09/26
ITEM:2
CÓD. (PN LORAM)A80680
QTD: 2,0000
DESCRIÇÃO: Suporte Formado
PREÇO UNIT: 488,50
VALOR TOTAL: 977,00
DATA DE ENTREGA  11/09/26
TOTAL PRODUTOS 1.603,00
TOTAL GERAL 1.603,00
T;

        $r = ($this->parser)->extrair($texto);

        $this->assertSame('0826-000012', $r['pedido']['numero']);
        $this->assertSame('2026-08-04', $r['pedido']['data_pedido']);
        $this->assertSame('Connectrail LTDA', $r['pedido']['fornecedor']);
        $this->assertSame('1603.00', $r['pedido']['valor']);
        $this->assertCount(2, $r['itens']);
        $this->assertSame('A80680', $r['itens'][1]['material']);
        $this->assertSame('977.00', $r['itens'][1]['vlr_tot']);
        $this->assertSame('2026-09-11', $r['itens'][1]['dt_entrega']);
        $this->assertSame('45DDL', $r['dados_extras']['cond_pgto']);
    }

    public function test_aceita_quantidade_decimal_e_unidade_acentuada(): void
    {
        $r = ($this->parser)->extrair("Item Material Denominação\n0001065629 CARTUCHO\n1.600 PEÇ 12,00 19.200,00    19,50 %     0,00 %\nTOTAIS:");

        $this->assertCount(1, $r['itens']);
        $this->assertSame('1600', $r['itens'][0]['qtd']);
        $this->assertSame('PEÇ', $r['itens'][0]['un']);
        $this->assertSame('19200.00', $r['itens'][0]['vlr_tot']);
    }
}
