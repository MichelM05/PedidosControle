<?php

namespace Tests\Feature;

use App\Models\Historico;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HistoricoTest extends TestCase
{
    use RefreshDatabase;

    private User $ana;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ana = User::factory()->create(['name' => 'Ana']);
        $this->actingAs($this->ana);
    }

    private function pedido(array $dados = [], array $itens = []): Pedido
    {
        $pedido = Pedido::create($dados + ['numero' => '100', 'cliente' => 'Rumo', 'data_pedido' => '2026-02-01', 'valor' => 100]);
        foreach ($itens as $item) {
            $pedido->itens()->create($item);
        }
        Historico::query()->delete(); // parte de um histórico limpo: os testes registram só o que fazem

        return $pedido;
    }

    private function registros(): Collection
    {
        return Historico::orderBy('id')->get();
    }

    public function test_registra_quem_quando_o_que_e_de_para_ao_editar_o_pedido(): void
    {
        $pedido = $this->pedido();

        $this->patch(route('pedidos.dados', $pedido), ['secao' => 'resumo', 'numero' => '100', 'cliente' => 'Loram', 'valor' => '250.50', 'data_pedido' => '2026-03-05', 'fornecedor' => 'Acme']);

        $r = $this->registros()->keyBy('campo');
        $this->assertSame(['Cliente', 'Data do pedido', 'Fornecedor', 'Valor total'], $r->keys()->sort()->values()->all());
        $this->assertSame(['Rumo', 'Loram'], [$r['Cliente']->valor_anterior, $r['Cliente']->valor_novo]);
        $this->assertSame(['01/02/2026', '05/03/2026'], [$r['Data do pedido']->valor_anterior, $r['Data do pedido']->valor_novo]);
        $this->assertSame([null, 'Acme'], [$r['Fornecedor']->valor_anterior, $r['Fornecedor']->valor_novo]);
        $this->assertSame(['100', '250.5'], [$r['Valor total']->valor_anterior, $r['Valor total']->valor_novo]);

        foreach ($r as $linha) {
            $this->assertSame('editou', $linha->acao);
            $this->assertSame('Ana', $linha->usuario_nome);
            $this->assertSame($this->ana->id, $linha->user_id);
            $this->assertSame($pedido->id, $linha->pedido_id);
            $this->assertSame('100', $linha->pedido_numero);
            $this->assertNotNull($linha->created_at);
        }
        $this->assertCount(1, $r->pluck('lote')->unique()); // tudo do mesmo salvamento
    }

    public function test_nao_registra_o_que_nao_mudou(): void
    {
        $pedido = $this->pedido();

        $this->patch(route('pedidos.dados', $pedido), ['secao' => 'resumo', 'numero' => '100', 'cliente' => 'Rumo', 'valor' => '100.00', 'data_pedido' => '2026-02-01']);

        $this->assertCount(0, $this->registros());
    }

    public function test_registra_condicoes_observacoes_e_blocos_pelo_rotulo(): void
    {
        $pedido = $this->pedido(['dados_extras' => ['frete' => 'CIF', 'blocos' => ['fornecedor' => ['nome' => 'A', 'endereco' => ['Rua 1']]]]]);

        $this->patch(route('pedidos.dados', $pedido), ['secao' => 'condicoes', 'frete' => 'FOB', 'cond_pgto' => '30 dias']);
        $this->patch(route('pedidos.dados', $pedido), ['secao' => 'observacoes', 'observacoes' => 'Entregar de manhã']);
        $this->patch(route('pedidos.dados', $pedido), ['secao' => 'fornecedor', 'nome' => 'B', 'endereco' => "Rua 2\nCuritiba", 'cnpj' => '04353498000127']);

        $r = $this->registros()->keyBy('campo');
        $this->assertSame(['CIF', 'FOB'], [$r['Frete']->valor_anterior, $r['Frete']->valor_novo]);
        $this->assertSame([null, '30 dias'], [$r['Condição de pagamento']->valor_anterior, $r['Condição de pagamento']->valor_novo]);
        $this->assertSame('Entregar de manhã', $r['Observações']->valor_novo);
        $this->assertSame('A · Rua 1', $r['Fornecedor']->valor_anterior);
        $this->assertSame('B · Rua 2, Curitiba · CNPJ 04353498000127', $r['Fornecedor']->valor_novo);
        $this->assertCount(2, $this->registros()->where('campo', 'Fornecedor')); // o nome do pedido e o bloco mudam juntos (sincronizados)
    }

    public function test_formulario_atualiza_itens_pelo_id_e_registra_so_o_que_mudou(): void
    {
        $pedido = $this->pedido([], [['item' => '1', 'denominacao' => 'Caixa', 'qtd' => 10, 'dt_entrega' => '2026-05-01', 'status' => 'andamento'], ['item' => '2', 'denominacao' => 'Poste', 'qtd' => 2]]);
        [$caixa, $poste] = $pedido->itens->all();
        Historico::query()->delete();

        $this->put(route('pedidos.update', $pedido), ['numero' => '100', 'cliente' => 'Rumo', 'itens' => [
            ['id' => $caixa->id, 'item' => '1', 'denominacao' => 'Caixa', 'qtd' => '10', 'dt_entrega' => '2026-05-10', 'status' => 'finalizado', 'responsavel' => 'Jorge'],
            ['denominacao' => 'Antena', 'qtd' => 3], // novo; o Poste foi removido
        ]]);

        $this->assertSame($caixa->id, $pedido->itens()->where('denominacao', 'Caixa')->value('id')); // o id é preservado
        $this->assertDatabaseMissing('pedido_itens', ['id' => $poste->id]);

        $porCampo = $this->registros()->where('acao', 'editou')->keyBy('campo');
        $this->assertSame(['Data de entrega', 'Responsável', 'Status'], $porCampo->keys()->sort()->values()->all());
        $this->assertSame(['01/05/2026', '10/05/2026'], [$porCampo['Data de entrega']->valor_anterior, $porCampo['Data de entrega']->valor_novo]);
        $this->assertSame(['Andamento', 'Finalizado'], [$porCampo['Status']->valor_anterior, $porCampo['Status']->valor_novo]);
        $this->assertSame('#1 Caixa', $porCampo['Status']->item_descricao);

        $this->assertTrue($this->registros()->contains(fn ($h) => $h->acao === 'criou' && $h->item_descricao === 'Antena'));
        $this->assertTrue($this->registros()->contains(fn ($h) => $h->acao === 'excluiu' && $h->item_descricao === '#2 Poste'));
        $this->assertCount(1, $this->registros()->pluck('lote')->unique());
    }

    public function test_criar_pedido_manual_registra_uma_linha_resumida_sem_um_registro_por_item(): void
    {
        $this->post(route('pedidos.store'), ['numero' => '777', 'itens' => [['denominacao' => 'a'], ['denominacao' => 'b']]]);

        $r = $this->registros();
        $this->assertCount(1, $r);
        $this->assertSame(['criou', 'Criado manualmente', '2 item(ns)', '777'], [$r[0]->acao, $r[0]->origem, $r[0]->valor_novo, $r[0]->pedido_numero]);
        $this->assertNull($r[0]->item_id);
    }

    public function test_status_em_lote_e_controle_do_item_entram_no_historico(): void
    {
        $pedido = $this->pedido([], [['denominacao' => 'a', 'status' => 'andamento'], ['denominacao' => 'b', 'status' => 'finalizado']]);

        $this->patch(route('pedidos.status', $pedido), ['status' => 'entregue']);
        $status = $this->registros()->where('campo', 'Status');
        $this->assertCount(2, $status);
        $this->assertEqualsCanonicalizing([['Andamento', 'Entregue'], ['Finalizado', 'Entregue']], $status->map(fn ($h) => [$h->valor_anterior, $h->valor_novo])->all());
        $this->assertCount(1, $status->pluck('lote')->unique());

        Historico::query()->delete();
        $this->patch(route('controle.atualizar', $pedido->itens->first()), ['status' => 'entregue', 'solda' => '12/03/2026', 'responsavel' => 'Jorge']);
        $this->assertEqualsCanonicalizing(['Solda', 'Responsável'], $this->registros()->pluck('campo')->all());
    }

    public function test_excluir_pedido_deixa_o_registro_legivel_mesmo_depois(): void
    {
        $pedido = $this->pedido(['numero' => '555']);

        $this->delete(route('pedidos.destroy', $pedido));

        $h = $this->registros()->first();
        $this->assertSame(['excluiu', '555', 'Ana'], [$h->acao, $h->pedido_numero, $h->usuario_nome]);
        $this->assertDatabaseMissing('pedidos', ['id' => $pedido->id]);
    }

    public function test_alteracao_sem_usuario_logado_aparece_como_sistema_e_o_nome_sobrevive_ao_usuario(): void
    {
        $pedido = $this->pedido();
        $this->patch(route('pedidos.dados', $pedido), ['secao' => 'resumo', 'numero' => '100', 'cliente' => 'Nova']);
        $this->ana->delete(); // o registro continua com o nome (user_id vira nulo)

        $h = $this->registros()->first();
        $this->assertSame('Ana', $h->usuario_nome);
        $this->assertNull($h->user_id);

        auth()->logout();
        Historico::query()->delete();
        $pedido->update(['cliente' => 'Por comando']);
        $this->assertSame('Sistema', $this->registros()->first()->usuario_nome);
    }

    public function test_tela_de_historico_lista_agrupa_filtra_e_pagina_por_salvamento(): void
    {
        $pedido = $this->pedido(['numero' => '100']);
        $outro = $this->pedido(['numero' => '200']);
        $this->patch(route('pedidos.dados', $pedido), ['secao' => 'resumo', 'numero' => '100', 'cliente' => 'A', 'fornecedor' => 'F']);
        $this->actingAs(User::factory()->create(['name' => 'Bia']))->patch(route('pedidos.dados', $outro), ['secao' => 'resumo', 'numero' => '200', 'cliente' => 'B']);

        $this->get(route('historico.index'))->assertInertia(fn (Assert $page) => $page->component('Historico/Index')
            ->has('grupos.data', 2)->where('grupos.data.0.usuario', 'Bia')->where('grupos.data.0.pedido_numero', '200')
            ->has('grupos.data.1.registros', 2)->where('grupos.data.1.usuario', 'Ana')->has('usuarios', 2));

        $filtro = fn (array $f) => $this->get(route('historico.index', $f))->viewData('page')['props']['grupos']['data'];
        $this->assertCount(1, $filtro(['pedido' => $pedido->id]));
        $this->assertCount(1, $filtro(['usuario' => $this->ana->id]));
        $this->assertCount(1, $filtro(['q' => '200']));
        $this->assertCount(2, $filtro(['acao' => 'editou']));
        $this->assertCount(0, $filtro(['acao' => 'excluiu']));
        $this->assertCount(2, $filtro(['de' => now()->format('Y-m-d'), 'ate' => now()->format('Y-m-d')]));
        $this->assertCount(0, $filtro(['de' => '2020-01-01', 'ate' => '2020-12-31']));
        $this->get(route('historico.index', ['acao' => 'xyz']))->assertSessionHasErrors('acao');
    }

    public function test_pagina_do_pedido_recebe_o_historico_dele(): void
    {
        $pedido = $this->pedido();
        $this->patch(route('pedidos.dados', $pedido), ['secao' => 'resumo', 'numero' => '100', 'cliente' => 'Mudou']);

        $this->get(route('pedidos.show', $pedido))->assertInertia(fn (Assert $page) => $page
            ->has('historico', 1)->where('historico.0.usuario', 'Ana')->where('historico.0.registros.0.campo', 'Cliente'));
    }

    public function test_historico_exige_login(): void
    {
        auth()->logout();

        $this->get(route('historico.index'))->assertRedirect(route('login'));
    }
}
