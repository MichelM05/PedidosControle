<?php

namespace Tests\Feature;

use App\Models\Pedido;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SegurancaTest extends TestCase
{
    use RefreshDatabase;

    public function test_devtools_do_inertia_fica_desligado_e_nao_expoe_dados_sem_login(): void
    {
        $this->assertFalse(config('inertia.devtools.enabled'));

        $this->get('/_inertia/devtools/entries')->assertNotFound();
        $this->get('/_inertia/devtools/entries/qualquer')->assertNotFound();
    }

    public function test_robots_pede_para_nao_indexar_nada(): void
    {
        $this->assertStringContainsString('Disallow: /', file_get_contents(public_path('robots.txt')));
        $this->assertStringNotContainsString("Disallow:\n", file_get_contents(public_path('robots.txt')));
    }

    public function test_senha_fraca_e_recusada_em_todos_os_pontos(): void
    {
        $fracas = ['abcdefgh' => 'password.letters', '12345678' => 'password.letters'];
        $semNumero = 'abcdefgh';
        $semLetra = '12345678';

        // troca de senha e criação por administrador
        $ana = User::factory()->admin()->create(['password' => 'antiga123']);
        $this->actingAs($ana);
        $this->put(route('perfil.senha'), ['senha_atual' => 'antiga123', 'password' => $semNumero, 'password_confirmation' => $semNumero])->assertSessionHasErrors('password');
        $this->put(route('perfil.senha'), ['senha_atual' => 'antiga123', 'password' => $semLetra, 'password_confirmation' => $semLetra])->assertSessionHasErrors('password');
        $this->post(route('usuarios.store'), ['name' => 'Y', 'email' => 'y@empresa.com', 'password' => $semNumero])->assertSessionHasErrors('password');
        $this->post(route('usuarios.store'), ['name' => 'Y', 'email' => 'y@empresa.com', 'password' => 'forte1234'])->assertSessionHasNoErrors();

        // comando
        $this->artisan('usuarios:criar', ['email' => 'z@empresa.com', '--nome' => 'Z', '--senha' => $semNumero])->assertFailed();
        $this->artisan('usuarios:criar', ['email' => 'z@empresa.com', '--nome' => 'Z', '--senha' => 'forte1234'])->assertSuccessful();
    }

    public function test_erro_ao_processar_pdf_nao_vaza_detalhes_tecnicos(): void
    {
        $this->actingAs(User::factory()->create());
        $quebrado = UploadedFile::fake()->createWithContent('pedido.pdf', "%PDF-1.4\n1 0 obj << /Type /Catalog /Pages 99 0 R >> endobj\n%%EOF");

        $this->post(route('pedidos.upload'), ['pdf' => $quebrado])->assertSessionHasErrors('pdf');

        $mensagem = session('errors')->first('pdf');
        $this->assertSame('Não foi possível processar este PDF. Confira se é um pedido válido e tente de novo.', $mensagem);
        $this->assertDatabaseCount('pedidos', 0);
    }

    public function test_upload_e_exportacao_tem_limite_de_requisicoes(): void
    {
        $this->actingAs(User::factory()->create());

        foreach (range(1, 20) as $i) {
            $this->post(route('pedidos.upload'))->assertStatus(302); // validação (sem arquivo), mas conta no limite
        }
        $this->post(route('pedidos.upload'))->assertStatus(429);
    }

    public function test_pdf_e_planilha_nao_ficam_em_cache(): void
    {
        Storage::fake();
        Storage::put('pdfs/x.pdf', '%PDF-1.4 teste');
        $pedido = Pedido::create(['numero' => '1', 'arquivo_pdf' => 'pdfs/x.pdf']);
        $this->actingAs(User::factory()->create());

        $this->get(route('pedidos.pdf', $pedido))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->get(route('controle.exportar'))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_comando_de_verificacao_aponta_problemas_e_aprova_ambiente_seguro(): void
    {
        $this->artisan('seguranca:verificar')->assertFailed(); // ambiente de teste/desenvolvimento

        config([
            'app.env' => 'production', 'app.debug' => false, 'app.url' => 'https://pedidos.exemplo.com', 'app.trusted_proxies' => '10.0.0.1', 'session.secure' => true, 'database.connections.sqlite.password' => 'x9!Qm2$vL7#kP4wZ',
        ]);
        $this->app['env'] = 'production';
        User::factory()->admin()->create();

        $this->artisan('seguranca:verificar')->assertSuccessful();
    }
}
