<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CriarUsuario extends Command
{
    protected $signature = 'usuarios:criar {email} {--nome= : Nome do usuário} {--senha= : Senha (se omitida, será perguntada)} {--admin : Cria como administrador}';

    protected $description = 'Cria um usuário (use --admin para o primeiro administrador)';

    public function handle(): int
    {
        $email = $this->argument('email');
        if (User::where('email', $email)->exists()) {
            $this->error("Já existe um usuário com o e-mail {$email}.");

            return self::FAILURE;
        }

        $nome = $this->option('nome') ?: $this->ask('Nome');
        $senha = $this->option('senha') ?: $this->secret('Senha (mínimo 8 caracteres, com letras e números)');
        if (strlen((string) $senha) < 8 || ! preg_match('/[A-Za-z]/', (string) $senha) || ! preg_match('/\d/', (string) $senha)) {
            $this->error('A senha deve ter pelo menos 8 caracteres, com letras e números.');

            return self::FAILURE;
        }

        User::create(['name' => $nome, 'email' => $email, 'password' => $senha, 'is_admin' => $this->option('admin')]);
        $this->info('Usuário criado'.($this->option('admin') ? ' como administrador' : '').'.');

        return self::SUCCESS;
    }
}
