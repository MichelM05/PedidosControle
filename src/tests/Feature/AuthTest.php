<?php

namespace Tests\Feature;

use App\Models\Pedido;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitante_e_enviado_para_o_login_em_todas_as_telas(): void
    {
        $pedido = Pedido::create(['numero' => '1']);

        foreach ([
            route('pedidos.index'), route('pedidos.create'), route('pedidos.show', $pedido), route('pedidos.edit', $pedido),
            route('controle.index'), route('controle.exportar'), route('perfil.edit'), route('usuarios.index'),
        ] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }

        $this->post(route('pedidos.store'), ['numero' => '2'])->assertRedirect(route('login'));
        $this->delete(route('pedidos.destroy', $pedido))->assertRedirect(route('login'));
        $this->assertDatabaseCount('pedidos', 1);
    }

    public function test_tela_de_login_abre_para_visitante_e_redireciona_quem_ja_entrou(): void
    {
        $this->get(route('login'))->assertOk()->assertInertia(fn (Assert $page) => $page->component('Auth/Login'));

        $this->actingAs(User::factory()->create())->get(route('login'))->assertRedirect('/');
    }

    public function test_entra_com_email_e_senha_e_volta_para_onde_queria_ir(): void
    {
        $usuario = User::factory()->create(['email' => 'ana@empresa.com', 'password' => 'segredo123']);

        $this->get(route('controle.index'))->assertRedirect(route('login'));
        $this->post(route('login.store'), ['email' => 'ana@empresa.com', 'password' => 'segredo123'])->assertRedirect(route('controle.index'));

        $this->assertAuthenticatedAs($usuario);
    }

    public function test_login_com_dados_errados_mostra_mensagem_unica(): void
    {
        User::factory()->create(['email' => 'ana@empresa.com', 'password' => 'segredo123']);

        $this->from(route('login'))->post(route('login.store'), ['email' => 'ana@empresa.com', 'password' => 'errada'])
            ->assertRedirect(route('login'))->assertSessionHasErrors(['email' => 'E-mail ou senha incorretos.']);
        $this->post(route('login.store'), ['email' => 'naoexiste@empresa.com', 'password' => 'segredo123'])
            ->assertSessionHasErrors(['email' => 'E-mail ou senha incorretos.']);
        $this->post(route('login.store'), ['email' => '', 'password' => ''])->assertSessionHasErrors(['email', 'password']);

        $this->assertGuest();
    }

    public function test_usuario_desativado_nao_consegue_entrar(): void
    {
        User::factory()->inativo()->create(['email' => 'ana@empresa.com', 'password' => 'segredo123']);

        $this->post(route('login.store'), ['email' => 'ana@empresa.com', 'password' => 'segredo123'])
            ->assertSessionHasErrors(['email' => 'E-mail ou senha incorretos.']);

        $this->assertGuest();
    }

    public function test_lembrar_me_grava_o_token_e_sair_encerra_a_sessao(): void
    {
        $usuario = User::factory()->create(['email' => 'ana@empresa.com', 'password' => 'segredo123']);

        $this->post(route('login.store'), ['email' => 'ana@empresa.com', 'password' => 'segredo123', 'remember' => true]);
        $this->assertAuthenticatedAs($usuario);
        $this->assertNotNull($usuario->refresh()->remember_token);

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_paginas_recebem_o_usuario_logado(): void
    {
        $this->actingAs(User::factory()->create(['name' => 'Ana', 'email' => 'ana@empresa.com']))
            ->get(route('pedidos.index'))
            ->assertInertia(fn (Assert $page) => $page->where('auth.user.name', 'Ana')->where('auth.user.is_admin', false)->missing('auth.user.password'));
    }

    public function test_usuario_troca_nome_e_email_mas_nao_usa_email_de_outro(): void
    {
        User::factory()->create(['email' => 'outro@empresa.com']);
        $ana = User::factory()->create(['name' => 'Ana', 'email' => 'ana@empresa.com']);

        $this->actingAs($ana)->patch(route('perfil.update'), ['name' => 'Ana Souza', 'email' => 'ana.souza@empresa.com'])->assertSessionHas('success');
        $this->assertSame('Ana Souza', $ana->refresh()->name);
        $this->assertSame('ana.souza@empresa.com', $ana->email);

        $this->patch(route('perfil.update'), ['name' => 'Ana', 'email' => 'outro@empresa.com'])->assertSessionHasErrors('email');
        $this->patch(route('perfil.update'), ['name' => 'Ana Souza', 'email' => 'ana.souza@empresa.com'])->assertSessionHasNoErrors(); // o próprio e-mail é permitido
    }

    public function test_troca_de_senha_exige_a_senha_atual_e_confirmacao(): void
    {
        $ana = User::factory()->create(['password' => 'antiga123']);
        $this->actingAs($ana);

        $this->put(route('perfil.senha'), ['senha_atual' => 'errada', 'password' => 'nova12345', 'password_confirmation' => 'nova12345'])->assertSessionHasErrors('senha_atual');
        $this->put(route('perfil.senha'), ['senha_atual' => 'antiga123', 'password' => 'curta', 'password_confirmation' => 'curta'])->assertSessionHasErrors('password');
        $this->put(route('perfil.senha'), ['senha_atual' => 'antiga123', 'password' => 'nova12345', 'password_confirmation' => 'outra12345'])->assertSessionHasErrors('password');
        $this->put(route('perfil.senha'), ['senha_atual' => 'antiga123', 'password' => 'antiga123', 'password_confirmation' => 'antiga123'])->assertSessionHasErrors('password');

        $this->put(route('perfil.senha'), ['senha_atual' => 'antiga123', 'password' => 'nova12345', 'password_confirmation' => 'nova12345'])->assertSessionHas('success');
        $this->assertTrue(\Hash::check('nova12345', $ana->refresh()->password));
        $this->assertNotSame('nova12345', $ana->password); // guardada com hash
    }

    public function test_so_administrador_acessa_a_gestao_de_usuarios(): void
    {
        $this->actingAs(User::factory()->create())->get(route('usuarios.index'))->assertForbidden();
        $this->post(route('usuarios.store'), ['name' => 'X', 'email' => 'x@x.com', 'password' => '12345678'])->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())->get(route('usuarios.index'))
            ->assertOk()->assertInertia(fn (Assert $page) => $page->component('Usuarios/Index')->has('usuarios', 2));
    }

    public function test_administrador_cria_usuario_que_ja_consegue_entrar(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->post(route('usuarios.store'), ['name' => 'Jorge', 'email' => 'jorge@empresa.com', 'password' => 'senha1234', 'is_admin' => false, 'ativo' => true])->assertSessionHas('success');
        $this->post(route('usuarios.store'), ['name' => 'Outro', 'email' => 'jorge@empresa.com', 'password' => 'senha1234'])->assertSessionHasErrors('email');
        $this->post(route('usuarios.store'), ['name' => 'Sem senha', 'email' => 's@empresa.com'])->assertSessionHasErrors('password');

        auth()->logout();
        $this->post(route('login.store'), ['email' => 'jorge@empresa.com', 'password' => 'senha1234']);
        $this->assertAuthenticated();
        $this->assertFalse(auth()->user()->is_admin);
    }

    public function test_edicao_mantem_a_senha_quando_em_branco_e_troca_quando_informada(): void
    {
        $admin = User::factory()->admin()->create();
        $jorge = User::factory()->create(['password' => 'antiga123']);
        $this->actingAs($admin);

        $this->patch(route('usuarios.update', $jorge), ['name' => 'Jorge Novo', 'email' => $jorge->email, 'password' => '', 'is_admin' => true, 'ativo' => true]);
        $jorge->refresh();
        $this->assertSame('Jorge Novo', $jorge->name);
        $this->assertTrue($jorge->is_admin);
        $this->assertTrue(\Hash::check('antiga123', $jorge->password));

        $this->patch(route('usuarios.update', $jorge), ['name' => 'Jorge Novo', 'email' => $jorge->email, 'password' => 'novissima123', 'is_admin' => true, 'ativo' => true]);
        $this->assertTrue(\Hash::check('novissima123', $jorge->refresh()->password));
    }

    public function test_nao_desativa_nem_rebaixa_o_ultimo_administrador(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $this->patch(route('usuarios.update', $admin), ['name' => $admin->name, 'email' => $admin->email, 'is_admin' => true, 'ativo' => false])->assertSessionHasErrors('ativo');
        $this->patch(route('usuarios.update', $admin), ['name' => $admin->name, 'email' => $admin->email, 'is_admin' => false, 'ativo' => true])->assertSessionHasErrors('ativo');
        $this->assertTrue($admin->refresh()->is_admin && $admin->ativo);

        $segundo = User::factory()->admin()->create(); // com outro administrador ativo, pode
        $this->patch(route('usuarios.update', $admin), ['name' => $admin->name, 'email' => $admin->email, 'is_admin' => true, 'ativo' => false])->assertSessionHasNoErrors();
        $this->assertFalse($admin->refresh()->ativo);
        $this->assertTrue($segundo->refresh()->ativo);
    }

    public function test_comando_cria_o_primeiro_administrador(): void
    {
        $this->artisan('usuarios:criar', ['email' => 'admin@empresa.com', '--nome' => 'Admin', '--senha' => 'senha1234', '--admin' => true])->assertSuccessful();
        $this->assertTrue(User::where('email', 'admin@empresa.com')->firstOrFail()->is_admin);

        $this->artisan('usuarios:criar', ['email' => 'admin@empresa.com', '--nome' => 'X', '--senha' => 'senha1234'])->assertFailed();
        $this->artisan('usuarios:criar', ['email' => 'fraco@empresa.com', '--nome' => 'X', '--senha' => '123'])->assertFailed();
    }

    public function test_quem_e_desativado_com_a_sessao_aberta_e_desconectado(): void
    {
        $usuario = User::factory()->create();
        $this->actingAs($usuario)->get(route('pedidos.index'))->assertOk();

        $usuario->update(['ativo' => false]);

        $this->get(route('pedidos.index'))->assertRedirect(route('login'))->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_tela_de_login_informa_se_o_cadastro_esta_aberto_e_se_e_o_primeiro_acesso(): void
    {
        $this->get(route('login'))->assertInertia(fn (Assert $page) => $page->where('registroAberto', true)->where('primeiroAcesso', true));

        User::factory()->create();
        $this->get(route('login'))->assertInertia(fn (Assert $page) => $page->where('primeiroAcesso', false));
    }

    public function test_primeiro_cadastro_vira_administrador_e_os_seguintes_sao_usuarios_comuns(): void
    {
        $dados = fn (string $nome, string $email) => ['name' => $nome, 'email' => $email, 'password' => 'senha12345', 'password_confirmation' => 'senha12345'];

        $this->post(route('registro.store'), $dados('Ana', 'ana@empresa.com'))->assertRedirect(route('pedidos.index'))->assertSessionHas('success');
        $this->assertAuthenticated();
        $this->assertTrue(User::where('email', 'ana@empresa.com')->firstOrFail()->is_admin);

        auth()->logout();
        $this->post(route('registro.store'), $dados('Bia', 'bia@empresa.com'));
        $bia = User::where('email', 'bia@empresa.com')->firstOrFail();
        $this->assertFalse($bia->is_admin);
        $this->assertTrue($bia->ativo);
        $this->assertTrue(\Hash::check('senha12345', $bia->password));
        $this->assertAuthenticatedAs($bia);
    }

    public function test_cadastro_valida_os_dados(): void
    {
        User::factory()->create(['email' => 'ana@empresa.com']);

        $this->post(route('registro.store'), ['name' => '', 'email' => 'invalido', 'password' => '123', 'password_confirmation' => '999'])
            ->assertSessionHasErrors(['name', 'email', 'password']);
        $this->post(route('registro.store'), ['name' => 'X', 'email' => 'ana@empresa.com', 'password' => 'senha12345', 'password_confirmation' => 'senha12345'])
            ->assertSessionHasErrors(['email' => 'Já existe uma conta com este e-mail.']);
        $this->post(route('registro.store'), ['name' => 'X', 'email' => 'x@empresa.com', 'password' => 'senha12345', 'password_confirmation' => 'diferente1'])
            ->assertSessionHasErrors(['password' => 'A confirmação da senha não confere.']);

        $this->assertGuest();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_cadastro_pode_ser_fechado_pela_configuracao(): void
    {
        config(['app.registro_aberto' => false]);

        $this->get(route('login'))->assertInertia(fn (Assert $page) => $page->where('registroAberto', false));
        $this->post(route('registro.store'), ['name' => 'X', 'email' => 'x@empresa.com', 'password' => 'senha12345', 'password_confirmation' => 'senha12345'])->assertNotFound();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_quem_ja_entrou_nao_acessa_o_cadastro(): void
    {
        $this->actingAs(User::factory()->create())->post(route('registro.store'), ['name' => 'X', 'email' => 'x@empresa.com', 'password' => 'senha12345', 'password_confirmation' => 'senha12345'])
            ->assertRedirect('/');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_login_bloqueia_temporariamente_depois_de_varias_senhas_erradas(): void
    {
        User::factory()->create(['email' => 'ana@empresa.com', 'password' => 'segredo123']);

        foreach (range(1, 5) as $i) {
            $this->post(route('login.store'), ['email' => 'ana@empresa.com', 'password' => "errada$i"])->assertSessionHasErrors(['email' => 'E-mail ou senha incorretos.']);
        }

        // a 6ª tentativa é barrada, mesmo com a senha certa
        $this->post(route('login.store'), ['email' => 'ana@empresa.com', 'password' => 'segredo123'])
            ->assertSessionHasErrors('email');
        $this->assertStringContainsString('Muitas tentativas', session('errors')->first('email'));
        $this->assertGuest();

        // outro e-mail não é afetado
        User::factory()->create(['email' => 'bia@empresa.com', 'password' => 'segredo123']);
        $this->post(route('login.store'), ['email' => 'bia@empresa.com', 'password' => 'segredo123']);
        $this->assertAuthenticated();
    }

    public function test_login_certo_zera_a_contagem_de_erros(): void
    {
        User::factory()->create(['email' => 'ana@empresa.com', 'password' => 'segredo123']);

        foreach (range(1, 4) as $i) {
            $this->post(route('login.store'), ['email' => 'ana@empresa.com', 'password' => "errada$i"]);
        }
        $this->post(route('login.store'), ['email' => 'ana@empresa.com', 'password' => 'segredo123']);
        $this->assertAuthenticated();

        auth()->logout();
        foreach (range(1, 4) as $i) { // recomeça do zero: 4 erros não bloqueiam
            $this->post(route('login.store'), ['email' => 'ana@empresa.com', 'password' => "outra$i"]);
        }
        $this->post(route('login.store'), ['email' => 'ana@empresa.com', 'password' => 'segredo123']);
        $this->assertAuthenticated();
    }

    public function test_cadastro_tem_limite_por_endereco(): void
    {
        foreach (range(1, 10) as $i) {
            auth()->logout();
            $this->post(route('registro.store'), ['name' => "U$i", 'email' => "u$i@empresa.com", 'password' => 'senha12345', 'password_confirmation' => 'senha12345']);
        }
        auth()->logout();

        $this->post(route('registro.store'), ['name' => 'Extra', 'email' => 'extra@empresa.com', 'password' => 'senha12345', 'password_confirmation' => 'senha12345'])
            ->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('users', ['email' => 'extra@empresa.com']);
    }

    public function test_respostas_trazem_cabecalhos_de_seguranca_e_csp_nas_paginas(): void
    {
        $resposta = $this->get(route('login'));

        $resposta->assertHeader('X-Frame-Options', 'DENY')->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')->assertHeaderMissing('X-Powered-By');
        $csp = $resposta->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);

        $this->get(route('login'), ['X-Inertia' => 'true', 'X-Inertia-Version' => 'x'])->assertHeader('X-Frame-Options', 'DENY'); // respostas JSON do Inertia também
    }

    public function test_hsts_so_em_conexao_segura_e_pdf_sem_csp(): void
    {
        $this->get(route('login'))->assertHeaderMissing('Strict-Transport-Security');
        $this->get('https://localhost/login')->assertHeader('Strict-Transport-Security');
    }
}
