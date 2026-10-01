<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_aplicacao_exige_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_a_tela_inicial_abre_para_usuario_logado(): void
    {
        $this->actingAs(User::factory()->create())->get('/')->assertOk();
    }
}
