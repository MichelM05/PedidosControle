<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Confere as configurações que mais importam para um sistema publicado. Rode no servidor:
 * php artisan seguranca:verificar (sai com erro se houver algum problema grave).
 */
class VerificarSeguranca extends Command
{
    protected $signature = 'seguranca:verificar';

    protected $description = 'Confere as configurações de segurança do ambiente (APP_DEBUG, HTTPS, senhas, cadastro aberto...)';

    /** Senhas de banco que nunca devem ir para produção. */
    private const SENHAS_FRACAS = ['', 'secret', 'password', 'senha', '123456', '12345678', 'root', 'laravel', 'postgres', 'admin'];

    public function handle(): int
    {
        $itens = [];
        $ok = function (string $nome, string $detalhe = '') use (&$itens) {
            $itens[] = ['ok', $nome, $detalhe];
        };
        $aviso = function (string $nome, string $detalhe) use (&$itens) {
            $itens[] = ['aviso', $nome, $detalhe];
        };
        $erro = function (string $nome, string $detalhe) use (&$itens) {
            $itens[] = ['erro', $nome, $detalhe];
        };

        // Ambiente e depuração: a página de erro com APP_DEBUG=true mostra senhas e chaves do .env
        app()->isProduction() ? $ok('APP_ENV=production') : $erro('APP_ENV', 'está "'.app()->environment().'": use production (ambiente local liga ferramentas de depuração)');
        config('app.debug') ? $erro('APP_DEBUG', 'está true: erros mostram variáveis e senhas do .env. Use false') : $ok('APP_DEBUG=false');
        config('inertia.devtools.enabled') ? $erro('INERTIA_DEVTOOLS_ENABLED', 'ligado: expõe dados das requisições sem login em /_inertia/devtools/entries') : $ok('Inertia DevTools desligado');
        is_dir(storage_path('inertia-devtools')) ? $aviso('storage/inertia-devtools', 'existe com dados gravados: apague a pasta') : $ok('Sem dados do DevTools em disco');
        file_exists(public_path('hot')) ? $erro('public/hot', 'existe (npm run dev ativo): rode npm run build e apague o arquivo') : $ok('Sem servidor de desenvolvimento do Vite');

        // Chave e banco
        config('app.key') ? $ok('APP_KEY definida') : $erro('APP_KEY', 'vazia: rode php artisan key:generate');
        $senhaBanco = (string) config('database.connections.'.config('database.default').'.password');
        in_array(strtolower($senhaBanco), self::SENHAS_FRACAS, true) ? $erro('Senha do banco', 'é fraca ou padrão: troque por uma senha longa e aleatória') : $ok('Senha do banco não é a padrão');

        // HTTPS e cookies
        str_starts_with((string) config('app.url'), 'https://') ? $ok('APP_URL usa https') : $aviso('APP_URL', 'não usa https: sirva o sistema somente por HTTPS');
        (config('session.secure') || config('app.force_https')) ? $ok('Cookie de sessão seguro (somente HTTPS)') : $aviso('Cookie de sessão', 'não está marcado como seguro: defina SESSION_SECURE_COOKIE=true (ou FORCE_HTTPS=true) ao usar HTTPS');
        config('app.trusted_proxies') ? $ok('TRUSTED_PROXIES definido') : $aviso('TRUSTED_PROXIES', 'vazio: atrás de um proxy/CDN o IP real e o HTTPS não são reconhecidos (limites de login valem para o IP do proxy)');
        config('app.trusted_proxies') === '*' ? $aviso('TRUSTED_PROXIES=*', 'confia em qualquer origem: prefira listar os IPs do proxy') : null;

        // Acesso
        config('app.registro_aberto') ? $aviso('REGISTRO_ABERTO', 'true: qualquer pessoa que alcance o endereço cria uma conta e vê os pedidos. Use false depois de criar as contas') : $ok('Cadastro fechado');
        if (User::where('is_admin', true)->where('ativo', true)->exists()) {
            $ok('Existe um administrador ativo');
        } else {
            $aviso('Administrador', 'nenhum ativo: crie com php artisan usuarios:criar email --admin');
        }
        config('app.primeiro_cadastro_admin') && ! User::exists() && config('app.registro_aberto')
            ? $aviso('Primeiro cadastro', 'sistema vazio com cadastro aberto: quem se cadastrar primeiro vira administrador. Crie o admin por comando antes de expor') : null;

        // PHP e logs
        ini_get('expose_php') ? $aviso('expose_php', 'On: o PHP anuncia a versão no cabeçalho X-Powered-By. Use expose_php=Off') : $ok('expose_php desligado');
        in_array('debug', [config('logging.channels.single.level'), config('logging.channels.daily.level')], true)
            ? $aviso('LOG_LEVEL', 'debug: grava muito detalhe. Use warning ou error em produção') : $ok('Nível de log adequado');

        $this->newLine();
        foreach ($itens as [$tipo, $nome, $detalhe]) {
            $marca = ['ok' => '<info>✔</info>', 'aviso' => '<comment>⚠</comment>', 'erro' => '<error> ✖ </error>'][$tipo];
            $this->line("  {$marca} {$nome}".($detalhe ? " — {$detalhe}" : ''));
        }

        $erros = count(array_filter($itens, fn ($i) => $i[0] === 'erro'));
        $avisos = count(array_filter($itens, fn ($i) => $i[0] === 'aviso'));
        $this->newLine();
        $erros ? $this->error("{$erros} problema(s) grave(s) e {$avisos} aviso(s).") : $this->info("Nenhum problema grave ({$avisos} aviso(s)).");

        return $erros ? self::FAILURE : self::SUCCESS;
    }
}
