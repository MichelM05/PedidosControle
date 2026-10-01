<?php

namespace Tests\Feature;

use App\Models\Pedido;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PedidoCrudTest extends TestCase
{
    use RefreshDatabase;

    private function dados(array $extra = []): array
    {
        return array_merge([
            'numero' => '4502006271',
            'data_pedido' => '2026-02-11',
            'cliente' => 'Cliente X',
            'fornecedor' => 'Fornecedor Y',
            'valor' => '100.50',
            'itens' => [[
                'item' => '00010', 'denominacao' => 'Serviço', 'qtd' => '1', 'un' => 'UR',
                'preco' => '100.50', 'vlr_tot' => '100.50', 'dt_entrega' => '2026-03-13',
            ]],
        ], $extra);
    }

    public function test_cria_pedido_com_itens(): void
    {
        $r = $this->post(route('pedidos.store'), $this->dados());

        $pedido = Pedido::firstOrFail();
        $r->assertRedirect(route('pedidos.show', $pedido));
        $this->assertCount(1, $pedido->itens);
        $this->assertSame('2026-03-13', $pedido->itens[0]->dt_entrega->format('Y-m-d'));
    }

    public function test_atualiza_pedido_e_remove_todos_os_itens(): void
    {
        $this->post(route('pedidos.store'), $this->dados());
        $pedido = Pedido::firstOrFail();

        $this->put(route('pedidos.update', $pedido), ['numero' => '999'])->assertRedirect();

        $pedido->refresh();
        $this->assertSame('999', $pedido->numero);
        $this->assertCount(0, $pedido->itens);
    }

    public function test_exclui_pedido(): void
    {
        $this->post(route('pedidos.store'), $this->dados());
        $pedido = Pedido::firstOrFail();

        $this->delete(route('pedidos.destroy', $pedido))->assertRedirect(route('pedidos.index'));
        $this->assertDatabaseCount('pedidos', 0);
    }

    public function test_valida_campos_com_mensagens_em_portugues(): void
    {
        $this->post(route('pedidos.store'), ['valor' => 'abc'])
            ->assertSessionHasErrors(['numero', 'valor']);

        $erros = session('errors')->all();
        $this->assertContains('Informe o campo Número do pedido.', $erros);
        $this->assertContains('O campo Valor total deve ser um número.', $erros);
    }

    public function test_busca_por_numero_grande_e_pagina_mantem_filtros(): void
    {
        foreach (range(1, 16) as $i) {
            Pedido::create(['numero' => '45020062'.$i, 'cliente' => 'ACME']);
        }

        $this->get(route('pedidos.index', ['numero' => '45020062']))
            ->assertInertia(fn (Assert $page) => $page->component('Pedidos/Index')
                ->where('pedidos.total', 16)->has('pedidos.data', 15)->where('pedidos.data.0.itens_count', 0));

        $this->get(route('pedidos.index', ['numero' => '4502006211']))
            ->assertInertia(fn (Assert $page) => $page->has('pedidos.data', 1)->where('pedidos.data.0.numero', '4502006211'));

        $this->get(route('pedidos.index', ['cliente' => 'ACME', 'page' => 2]))
            ->assertInertia(fn (Assert $page) => $page->has('pedidos.data', 1)
                ->where('filtros.cliente', 'ACME')->where('pedidos.prev_page_url', fn ($url) => str_contains($url, 'cliente=ACME')));
    }

    public function test_lista_nao_carrega_texto_bruto_nem_itens(): void
    {
        Pedido::create(['numero' => '1', 'texto_bruto' => 'texto longo']);

        $this->get(route('pedidos.index'))
            ->assertInertia(fn (Assert $page) => $page->missing('pedidos.data.0.texto_bruto')->missing('pedidos.data.0.itens'));
    }

    public function test_telas_de_criar_editar_e_detalhes_renderizam_as_paginas_react(): void
    {
        $pedido = Pedido::create(['numero' => '123']);

        $this->get(route('pedidos.create'))->assertInertia(fn (Assert $page) => $page->component('Pedidos/Form')->where('pedido', null));
        $this->get(route('pedidos.edit', $pedido))->assertInertia(fn (Assert $page) => $page->component('Pedidos/Form')->where('pedido.numero', '123'));
        $this->get(route('pedidos.show', $pedido))->assertInertia(fn (Assert $page) => $page->component('Pedidos/Show')
            ->where('pedido.numero', '123')->where('pedido.tem_pdf', false)->has('rotulos.blocos')->has('rotulos.condicoes'));
    }

    public function test_mensagem_de_sucesso_e_compartilhada_com_as_paginas(): void
    {
        $this->post(route('pedidos.store'), $this->dados())->assertRedirect();

        $this->get(route('pedidos.index'))->assertInertia(fn (Assert $page) => $page->where('flash.success', 'Pedido criado com sucesso!'));
    }

    private function pedidoComExtras(): Pedido
    {
        return Pedido::create([
            'numero' => '123', 'cliente' => 'Cliente A', 'fornecedor' => 'Fornecedor B', 'valor' => 10,
            'dados_extras' => [
                'frete' => 'CIF',
                'blocos' => [
                    'fornecedor' => ['titulo' => 'Fornecedor', 'nome' => 'Fornecedor B', 'endereco' => ['Rua 1']],
                    'faturamento' => ['titulo' => 'Faturamento', 'nome' => 'Cliente A'],
                ],
            ],
        ]);
    }

    public function test_edita_resumo_sincronizando_nomes_dos_blocos_sem_mexer_nos_itens(): void
    {
        $pedido = $this->pedidoComExtras();
        $pedido->itens()->create(['item' => '1', 'vlr_tot' => 10]);

        $this->patch(route('pedidos.dados', $pedido), [
            'secao' => 'resumo', 'numero' => '999', 'cliente' => 'Novo Cliente', 'fornecedor' => 'Novo Forn', 'valor' => '55.5',
        ])->assertRedirect(route('pedidos.show', $pedido));

        $pedido->refresh();
        $this->assertSame('999', $pedido->numero);
        $this->assertSame('Novo Forn', $pedido->dados_extras['blocos']['fornecedor']['nome']);
        $this->assertSame('Novo Cliente', $pedido->dados_extras['blocos']['faturamento']['nome']);
        $this->assertSame('CIF', $pedido->dados_extras['frete']);
        $this->assertCount(1, $pedido->itens);
    }

    public function test_edita_condicoes_e_remove_campo_esvaziado(): void
    {
        $pedido = $this->pedidoComExtras();

        $this->patch(route('pedidos.dados', $pedido), [
            'secao' => 'condicoes', 'frete' => '', 'cond_pgto' => '30 dias', 'contato_email' => 'a@b.com',
        ])->assertRedirect();

        $extras = $pedido->refresh()->dados_extras;
        $this->assertArrayNotHasKey('frete', $extras);
        $this->assertSame('30 dias', $extras['cond_pgto']);
        $this->assertSame('a@b.com', $extras['contato_email']);
        $this->assertArrayHasKey('blocos', $extras);
    }

    public function test_edita_bloco_de_endereco_e_sincroniza_fornecedor(): void
    {
        $pedido = $this->pedidoComExtras();

        $this->patch(route('pedidos.dados', $pedido), [
            'secao' => 'fornecedor', 'nome' => 'ACME', 'endereco' => "Rua A\n\nCuritiba PR", 'cnpj' => '04353498000127',
        ])->assertRedirect();

        $pedido->refresh();
        $this->assertSame('ACME', $pedido->fornecedor);
        $this->assertSame(['Rua A', 'Curitiba PR'], $pedido->dados_extras['blocos']['fornecedor']['endereco']);
        $this->assertSame('Fornecedor', $pedido->dados_extras['blocos']['fornecedor']['titulo']);
    }

    public function test_edita_observacoes_e_valida_erros_da_secao(): void
    {
        $pedido = $this->pedidoComExtras();

        $this->patch(route('pedidos.dados', $pedido), ['secao' => 'observacoes', 'observacoes' => "Linha 1\nLinha 2"])->assertRedirect();
        $this->assertSame("Linha 1\nLinha 2", $pedido->refresh()->dados_extras['observacoes']);

        $this->from(route('pedidos.show', $pedido))
            ->patch(route('pedidos.dados', $pedido), ['secao' => 'resumo', 'numero' => '', 'valor' => 'x'])
            ->assertRedirect(route('pedidos.show', $pedido))
            ->assertSessionHasErrors(['numero', 'valor']);
    }

    public function test_detalhes_enviam_os_dados_extras_para_a_tela(): void
    {
        $pedido = $this->pedidoComExtras();

        $this->get(route('pedidos.show', $pedido))->assertInertia(fn (Assert $page) => $page
            ->where('pedido.dados_extras.frete', 'CIF')
            ->where('pedido.dados_extras.blocos.fornecedor.nome', 'Fornecedor B'));
    }
}
