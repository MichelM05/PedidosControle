<?php

namespace Tests\Feature;

use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ControleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    private function pedido(string $numero, string $data, array $itens): Pedido
    {
        $pedido = Pedido::create(['numero' => $numero, 'cliente' => 'Rumo', 'data_pedido' => $data]);
        foreach ($itens as $item) {
            $pedido->itens()->create($item);
        }

        return $pedido;
    }

    private function planilha(string $conteudo)
    {
        $arquivo = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($arquivo, $conteudo);

        return IOFactory::load($arquivo);
    }

    public function test_grade_mostra_uma_linha_por_item_do_ano_com_totais_por_status(): void
    {
        $this->pedido('100', '2026-02-01', [
            ['denominacao' => 'A', 'qtd' => 2, 'status' => 'entregue'],
            ['denominacao' => 'B', 'status' => 'andamento'],
        ]);
        $this->pedido('200', '2025-05-01', [['denominacao' => 'C']]);

        $this->get(route('controle.index'))->assertInertia(fn (Assert $page) => $page
            ->component('Controle/Index')->where('ano', 2026)->where('anos', [2026, 2025])
            ->has('linhas', 2)->where('linhas.0.numero', '100')->where('linhas.0.cliente', 'Rumo')
            ->where('totais.todos', 2)->where('totais.entregue', 1)->where('totais.andamento', 1)
            ->has('colunas', 17)->where('colunas.0.chave', 'pedido')->where('colunas.0.oculta', true));

        $this->get(route('controle.index', ['ano' => 2025]))
            ->assertInertia(fn (Assert $page) => $page->has('linhas', 1)->where('linhas.0.denominacao', 'C'));
    }

    public function test_filtros_da_grade(): void
    {
        $this->pedido('100', '2026-02-01', [
            ['denominacao' => 'caixa', 'status' => 'entregue', 'responsavel' => 'Jorge', 'cidade_entrega' => 'Curitiba'],
            ['denominacao' => 'poste', 'status' => 'andamento', 'responsavel' => 'jorge'],
            ['denominacao' => 'antena', 'status' => 'andamento', 'responsavel' => 'Roger'],
        ]);

        $linhas = fn (array $filtros) => $this->get(route('controle.index', $filtros))->viewData('page')['props']['linhas'];

        $this->assertCount(1, $linhas(['q' => 'poste']));
        $this->assertCount(1, $linhas(['q' => 'curitiba']));
        $this->assertCount(3, $linhas(['q' => '100'])); // busca também pelo número do pedido
    }

    public function test_status_responsavel_e_ocultar_entregues(): void
    {
        $this->pedido('100', '2026-02-01', [
            ['denominacao' => 'a', 'status' => 'entregue', 'responsavel' => 'Jorge'],
            ['denominacao' => 'b', 'status' => 'andamento', 'responsavel' => 'jorge'],
            ['denominacao' => 'c', 'status' => 'andamento', 'responsavel' => 'Roger'],
        ]);

        $this->get(route('controle.index', ['status' => 'entregue']))->assertInertia(fn (Assert $p) => $p->has('linhas', 1));
        $this->get(route('controle.index', ['responsavel' => 'JORGE']))->assertInertia(fn (Assert $p) => $p->has('linhas', 2));
        $this->get(route('controle.index', ['ocultar_entregues' => 1]))->assertInertia(fn (Assert $p) => $p->has('linhas', 2)); // entregue sai
        $this->pedido('300', '2026-03-01', [['denominacao' => 'd', 'status' => 'cancelado']]);
        $this->get(route('controle.index', ['ocultar_entregues' => 1]))->assertInertia(fn (Assert $p) => $p->has('linhas', 2)); // cancelado também
        $this->get(route('controle.index', ['status' => 'invalido']))->assertSessionHasErrors('status');
    }

    public function test_edita_o_controle_do_item_e_volta_para_a_tela_do_pedido(): void
    {
        $pedido = $this->pedido('100', '2026-02-01', [['denominacao' => 'a', 'usinagem' => 'antiga']]);
        $item = $pedido->itens->first();

        $this->patch(route('controle.atualizar', $item), [
            'status' => 'finalizado', 'responsavel' => 'Jorge', 'cidade_entrega' => 'Curitiba',
            'dt_entrega' => '2026-05-01', 'usinagem' => '', 'solda' => '12/03/2026', 'compra_mp' => 'recebido 02/09',
        ])->assertRedirect(route('pedidos.show', $pedido))->assertSessionHas('success');

        $item->refresh();
        $this->assertSame('finalizado', $item->status);
        $this->assertSame('Jorge', $item->responsavel);
        $this->assertSame('Curitiba', $item->cidade_entrega);
        $this->assertSame('2026-05-01', $item->dt_entrega->format('Y-m-d'));
        $this->assertNull($item->usinagem);
        $this->assertSame('12/03/2026', $item->solda);
        $this->assertSame('a', $item->denominacao); // só o controle é editável aqui
    }

    public function test_edicao_do_controle_valida_status_e_nao_altera_outros_campos(): void
    {
        $pedido = $this->pedido('100', '2026-02-01', [['denominacao' => 'a', 'qtd' => 5]]);
        $item = $pedido->itens->first();
        $url = route('controle.atualizar', $item);

        $this->patch($url, ['status' => 'qualquer'])->assertSessionHasErrors('status');
        $this->patch($url, ['status' => ''])->assertSessionHasErrors('status');
        $this->patch($url, ['status' => 'andamento', 'dt_entrega' => 'abc'])->assertSessionHasErrors('dt_entrega');

        $this->patch($url, ['status' => 'andamento', 'qtd' => 99, 'pedido_id' => 9, 'denominacao' => 'hack']);
        $item->refresh();
        $this->assertSame('5.0000', $item->qtd);
        $this->assertSame($pedido->id, $item->pedido_id);
        $this->assertSame('a', $item->denominacao);
    }

    public function test_tela_do_pedido_envia_as_opcoes_de_status_e_o_controle_dos_itens(): void
    {
        $pedido = $this->pedido('100', '2026-02-01', [['denominacao' => 'a', 'status' => 'finalizado', 'responsavel' => 'Jorge']]);

        $this->get(route('pedidos.show', $pedido))->assertInertia(fn (Assert $page) => $page
            ->has('status', 4)->where('pedido.itens.0.status', 'finalizado')->where('pedido.itens.0.responsavel', 'Jorge'));
    }

    public function test_exporta_a_planilha_no_formato_do_modelo(): void
    {
        $this->pedido('4502006271', '2026-02-11', [[
            'denominacao' => 'suporte', 'qtd' => 90, 'dt_entrega' => '2026-03-13', 'cidade_entrega' => 'Mairinque',
            'desenho_nesting' => '10/02/2026', 'compra_mp' => 'recebido 02/09', 'usinagem' => 'xxxxx', 'responsavel' => 'Jorge', 'status' => 'entregue',
        ]]);
        $this->pedido('verbal', '2025-05-01', [['denominacao' => 'antigo', 'status' => 'andamento']]);

        $resposta = $this->get(route('controle.exportar'));
        $resposta->assertOk()->assertDownload('controle-de-pedidos.xlsx');

        $arquivo = $this->planilha($resposta->streamedContent());
        $this->assertSame(['2026', '2025'], $arquivo->getSheetNames());

        $aba = $arquivo->getSheetByName('2026');
        $titulos = array_map(fn ($c) => str_replace("\n", ' ', (string) $aba->getCell([$c, 2])->getValue()), range(1, 17));
        $this->assertSame(
            ['PEDIDO', 'CLIENTE', 'O.C CLIENTE', 'DESCRIÇÃO PRODUTO', 'QUANT.', 'DATA  DE  ENTREGA', 'CIDADE  ENTREGA', 'DESENHO NESTING', 'COMPRA  M.P', 'COMPRA  INSUMO', 'USINAGEM', 'CORTE E/OU DOBRA', 'SOLDA', 'PINTURA', 'MONTAGEM', 'RESPONSÁVEL', 'STATUS'],
            $titulos,
        );
        $this->assertSame('CONTROLE PEDIDOS', $aba->getCell('F1')->getValue());
        $this->assertSame('=TODAY()', $aba->getCell('Q1')->getValue());

        // dados: número como número, quantidade numérica, data real do Excel, etapas em data ou texto
        $this->assertSame('Rumo', $aba->getCell('B3')->getValue());
        $this->assertSame(4502006271, $aba->getCell('C3')->getValue());
        $this->assertEquals(90, $aba->getCell('E3')->getValue());
        $this->assertSame('13/03/2026', $aba->getCell('F3')->getFormattedValue());
        $this->assertSame('Mairinque', $aba->getCell('G3')->getValue());
        $this->assertSame('10/02/26', $aba->getCell('H3')->getFormattedValue());
        $this->assertSame('recebido 02/09', $aba->getCell('I3')->getValue());
        $this->assertSame('xxxxx', $aba->getCell('K3')->getValue());
        $this->assertSame('ENTREGUE', $aba->getCell('Q3')->getValue());
        $this->assertSame('verbal', $arquivo->getSheetByName('2025')->getCell('C3')->getValue());

        // formato: colunas A e B ocultas, cores do cabeçalho e fundo das linhas
        $this->assertFalse($aba->getColumnDimension('A')->getVisible());
        $this->assertFalse($aba->getColumnDimension('B')->getVisible());
        $this->assertTrue($aba->getColumnDimension('C')->getVisible());
        $this->assertSame('FFE7E6E6', $aba->getStyle('C2')->getFill()->getStartColor()->getARGB());
        $this->assertSame('FFFEF2CB', $aba->getStyle('I2')->getFill()->getStartColor()->getARGB());
        $this->assertSame('FFE2EFD9', $aba->getStyle('K2')->getFill()->getStartColor()->getARGB());
        $this->assertSame('FFD6DCE4', $aba->getStyle('Q2')->getFill()->getStartColor()->getARGB());
        $this->assertSame('FFECECEC', $aba->getStyle('D3')->getFill()->getStartColor()->getARGB());

        // regras de cor, em ordem de prioridade: cancelado, entregue, finalizado, urgente (10 dias) e alerta (20 dias)
        $regras = $aba->getConditionalStyles('A3');
        $this->assertCount(5, $regras);
        $this->assertSame('$Q3="CANCELADO"', $regras[0]->getConditions()[0]);
        $this->assertTrue($regras[0]->getStyle()->getFont()->getStrikethrough());
        $this->assertSame('$Q3="ENTREGUE"', $regras[1]->getConditions()[0]);
        $this->assertTrue($regras[1]->getStyle()->getFont()->getStrikethrough());
        $cores = array_map(fn ($r) => $r->getStyle()->getFill()->getEndColor()->getARGB(), $regras);
        $this->assertSame(['FFD9C7EA', 'FFA9D18E', 'FFBDD7EE', 'FFFF9999', 'FFFFD965'], $cores);
        $this->assertSame('AND($F3<>"",($F3-10)<=$Q$1)', $regras[3]->getConditions()[0]);
        $this->assertSame('AND($F3<>"",($F3-20)<=$Q$1)', $regras[4]->getConditions()[0]);
    }

    public function test_exporta_um_ano(): void
    {
        $this->pedido('100', '2026-02-01', [['denominacao' => 'a'], ['denominacao' => 'b']]);
        $this->pedido('200', '2025-05-01', [['denominacao' => 'c']]);

        $resposta = $this->get(route('controle.exportar', ['ano' => 2025]));
        $resposta->assertDownload('controle-de-pedidos-2025.xlsx');

        $arquivo = $this->planilha($resposta->streamedContent());
        $this->assertSame(['2025'], $arquivo->getSheetNames());
        $this->assertSame('c', $arquivo->getSheetByName('2025')->getCell('D3')->getValue());
    }

    public function test_status_cancelado_sai_riscado_na_planilha_e_conta_nos_totais(): void
    {
        $this->pedido('100', '2026-02-01', [['denominacao' => 'a', 'status' => 'cancelado'], ['denominacao' => 'b']]);

        $this->get(route('controle.index'))->assertInertia(fn (Assert $page) => $page->where('totais.cancelado', 1)->where('status.cancelado', 'Cancelado'));

        $aba = $this->planilha($this->get(route('controle.exportar'))->streamedContent())->getSheetByName('2026');
        $this->assertSame('CANCELADO', $aba->getCell('Q3')->getValue());
    }

    public function test_muda_o_status_de_todos_os_itens_do_pedido(): void
    {
        $pedido = $this->pedido('100', '2026-02-01', [['denominacao' => 'a', 'status' => 'andamento'], ['denominacao' => 'b', 'status' => 'finalizado']]);
        $outro = $this->pedido('200', '2026-02-01', [['denominacao' => 'c', 'status' => 'andamento']]);

        $this->patch(route('pedidos.status', $pedido), ['status' => 'entregue'])->assertSessionHas('success');

        $this->assertSame(['entregue', 'entregue'], $pedido->itens()->pluck('status')->all());
        $this->assertSame('andamento', $outro->itens()->first()->status); // não mexe em outros pedidos

        $this->patch(route('pedidos.status', $pedido), ['status' => 'xyz'])->assertSessionHasErrors('status');
        $this->patch(route('pedidos.status', $pedido), [])->assertSessionHasErrors('status');
    }

    public function test_status_de_um_unico_item_sem_alterar_o_resto_do_controle(): void
    {
        $pedido = $this->pedido('100', '2026-02-01', [['denominacao' => 'a', 'responsavel' => 'Jorge', 'solda' => 'x']]);
        $item = $pedido->itens->first();

        $this->patch(route('controle.atualizar', $item), ['status' => 'cancelado'])->assertSessionHasNoErrors();

        $item->refresh();
        $this->assertSame('cancelado', $item->status);
        $this->assertSame('Jorge', $item->responsavel);
        $this->assertSame('x', $item->solda);
    }

    public function test_editar_o_pedido_pelo_formulario_preserva_o_controle_dos_itens(): void
    {
        $pedido = $this->pedido('100', '2026-02-01', [['denominacao' => 'a', 'usinagem' => '12/03/2026', 'responsavel' => 'Jorge', 'status' => 'finalizado']]);
        $item = $pedido->itens->first();

        $itens = $this->get(route('pedidos.edit', $pedido))->viewData('page')['props']['pedido']['itens'];
        $this->assertSame('finalizado', $itens[0]['status']);

        $this->put(route('pedidos.update', $pedido), ['numero' => '100', 'itens' => $itens])->assertRedirect();

        $novo = $pedido->refresh()->itens->first();
        $this->assertSame('12/03/2026', $novo->usinagem);
        $this->assertSame('Jorge', $novo->responsavel);
        $this->assertSame('finalizado', $novo->status);
    }

    public function test_item_sem_status_entra_em_andamento_e_status_invalido_e_recusado(): void
    {
        $this->post(route('pedidos.store'), ['numero' => '1', 'itens' => [['denominacao' => 'x', 'status' => '']]])->assertSessionHasNoErrors();
        $this->assertSame('andamento', PedidoItem::firstOrFail()->status);

        $this->post(route('pedidos.store'), ['numero' => '2', 'itens' => [['denominacao' => 'y', 'status' => 'xyz']]])->assertSessionHasErrors('itens.0.status');
    }
}
