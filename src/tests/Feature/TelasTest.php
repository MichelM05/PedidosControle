<?php

namespace Tests\Feature;

use App\Models\Pedido;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/** Percorre todas as telas e o ciclo criar → ver → editar (com os campos novos do item) para pegar quebras de ponta a ponta. */
class TelasTest extends TestCase
{
    use RefreshDatabase;

    private function pedidoCompleto(): Pedido
    {
        $pedido = Pedido::create(['numero' => '700', 'cliente' => 'Rumo', 'fornecedor' => 'Forn', 'data_pedido' => '2026-03-01', 'valor' => 100]);
        $pedido->itens()->create([
            'item' => '10', 'denominacao' => 'Caixa', 'observacoes' => "linha 1\nlinha 2", 'fabricante' => 'hoffman',
            'qtd' => 2, 'preco' => 50, 'vlr_tot' => 100, 'dt_entrega' => '2026-04-01', 'cidade_entrega' => 'Curitiba', 'responsavel' => 'Ana',
        ]);

        return $pedido;
    }

    public function test_todas_as_telas_abrem(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $pedido = $this->pedidoCompleto();

        foreach ([
            route('pedidos.index'), route('pedidos.show', $pedido), route('pedidos.edit', $pedido), route('pedidos.create'),
            route('controle.index'), route('controle.index', ['ano' => 2026, 'status' => 'andamento']), route('historico.index'),
            route('usuarios.index'), route('perfil.edit'),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_detalhes_e_edicao_trazem_os_campos_novos_do_item(): void
    {
        $this->actingAs(User::factory()->create());
        $pedido = $this->pedidoCompleto();

        foreach (['pedidos.show', 'pedidos.edit'] as $rota) {
            $this->get(route($rota, $pedido))->assertInertia(fn (Assert $page) => $page
                ->where('pedido.itens.0.fabricante', 'HOFFMAN')
                ->where('pedido.itens.0.observacoes', "linha 1\nlinha 2"));
        }
    }

    public function test_salvar_pedido_com_observacoes_e_fabricante(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('pedidos.store'), [
            'numero' => '800',
            'itens' => [['denominacao' => 'Peça', 'qtd' => '1', 'observacoes' => 'nota', 'fabricante' => 'acme', 'status' => 'andamento']],
        ])->assertRedirect();

        $item = Pedido::where('numero', '800')->firstOrFail()->itens()->firstOrFail();
        $this->assertSame('ACME', $item->fabricante);
        $this->assertSame('nota', $item->observacoes);
    }
}
