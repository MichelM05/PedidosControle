# Segurança

Resultado da última revisão e o que fazer **no servidor**. Para conferir o ambiente a qualquer momento:

```bash
docker compose exec app php artisan seguranca:verificar
```

O comando aponta (✖ grave, ⚠ aviso): `APP_ENV`/`APP_DEBUG`, DevTools ligado, senha de banco padrão, HTTPS e cookie seguro, proxies, cadastro aberto,
administrador ativo, `expose_php` e nível de log. Sai com erro se houver algo grave, então serve também em rotinas de deploy.

## Corrigido na revisão mais recente

| Risco | O que acontecia | Correção |
|---|---|---|
| **Crítico** — Inertia DevTools | Com `APP_ENV=local` (o padrão do `.env` de desenvolvimento), o pacote gravava os dados de cada requisição em `storage/inertia-devtools` e os servia em `/_inertia/devtools/entries` **sem exigir login**: qualquer pessoa lia pedidos, clientes, fornecedores e valores. | Desligado por padrão (`config/inertia.php`, `INERTIA_DEVTOOLS_ENABLED=false`); a rota responde 404 e a pasta com dados gravados foi apagada. |
| Alto — erros técnicos na tela | Falha ao processar um PDF mostrava a mensagem interna da exceção ao usuário. | Mensagem genérica; o detalhe vai só para o log. Limite de texto por PDF. |
| Médio — senhas fracas | Aceitava `aaaaaaaa` ou `12345678`. | Mínimo de 8 caracteres com letras e números (cadastro, perfil, tela Usuários e comando). |
| Médio — "tomar" um sistema novo | Em um sistema vazio com cadastro aberto, o primeiro a se cadastrar virava administrador. | Em produção o padrão passa a ser **não**: crie o administrador por comando (`PRIMEIRO_CADASTRO_ADMIN`). |
| Médio — abuso de upload e exportação | Sem limite de requisições. | 20 uploads/min e 30 exportações/min por usuário. |
| Médio — IP e HTTPS atrás de proxy | Os limites de login/cadastro (por IP) e o HSTS dependem de o Laravel enxergar o IP real. | `TRUSTED_PROXIES` e `FORCE_HTTPS` configuráveis. |
| Baixo | PDFs e planilhas podiam ficar no cache do navegador; robots.txt liberava indexação; arquivos antigos em `public/` (axios, CSS). | `Cache-Control: no-store`; `Disallow: /`; arquivos removidos. |

## O que já estava protegido

- **Dependências:** `composer audit` e `npm audit` sem vulnerabilidades.
- **Login:** senhas com hash (bcrypt), sessão renovada ao entrar, mensagem única para e-mail/senha errados e conta desativada,
  bloqueio por 15 minutos depois de 5 senhas erradas (e-mail + IP), limite de 10 cadastros por hora por IP.
- **CSRF** em tudo que altera dados (sem token: 419). **Acesso:** todas as rotas exigem login; `/usuarios` só para administradores (403).
- **Injeção:** consultas com parâmetros; o React escapa tudo que mostra; células do Excel exportado são texto (nada vira fórmula).
- **Upload:** só PDF **verificado pelo conteúdo** (arquivo com código PHP/HTML renomeado para .pdf é recusado), até 10 MB, nome aleatório,
  guardado em `storage/app/private` e entregue só por rota autenticada.
- **Cabeçalhos:** `X-Frame-Options`, `nosniff`, `Referrer-Policy`, `Permissions-Policy`, `Content-Security-Policy` restritiva e HSTS em HTTPS.
- **Mass assignment:** só `validated()`; `is_admin` só pela tela de administrador. **Segredos:** `.env` fora do Git e nada no histórico.

## Checklist do servidor (depende de você)

1. **`src/.env` de produção.** Em desenvolvimento ele tem `APP_ENV=local` e `APP_DEBUG=true`: nesse modo uma página de erro mostra senhas e a `APP_KEY`.
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://seu-dominio
   LOG_LEVEL=warning
   SESSION_SECURE_COOKIE=true      # ou FORCE_HTTPS=true
   SESSION_ENCRYPT=true
   REGISTRO_ABERTO=false           # depois de criar as contas da equipe
   TRUSTED_PROXIES=10.0.0.5        # IP do seu proxy (nginx/Cloudflare); não deixe em branco atrás de proxy
   INERTIA_DEVTOOLS_ENABLED=false
   ```
2. **Se o sistema já esteve no ar com `APP_ENV=local` ou `APP_DEBUG=true`, trate os dados como expostos:**
   - troque a `APP_KEY` (`php artisan key:generate`; isso desconecta todos),
   - troque a senha do banco (no PostgreSQL e no `.env`) e as senhas de todos os usuários,
   - apague `storage/inertia-devtools` e confira se alguém acessou `/_inertia/devtools/entries` nos logs do servidor web.
3. **HTTPS obrigatório**, atrás de nginx/Caddy/Traefik. **Não use `php artisan serve` em produção**: use PHP-FPM + nginx.
4. **Docker:** o `docker-compose.yml` de desenvolvimento publica o PostgreSQL (`5432`) e o pgAdmin (`5050`) com a senha `secret`.
   Em servidor, não exponha essas portas (use `127.0.0.1:5432:5432` ou remova) e troque as senhas. Remova também o Xdebug da imagem e use `expose_php=Off`.
5. **Cadastro aberto:** com `REGISTRO_ABERTO=true` qualquer pessoa que alcance a URL cria uma conta e vê todos os pedidos. Feche assim que a equipe tiver contas.
6. **Código de produção:** `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build` (apague `public/hot`),
   `php artisan config:cache route:cache view:cache` e `php artisan migrate --force`.
7. **Backup** do banco e de `storage/app/private/pdfs` (nomes, e-mails, telefones e CNPJs de clientes: considere a LGPD).
8. Rode `php artisan seguranca:verificar` e resolva todos os ✖.

## Limitações conhecidas (decisões de produto)

- Todo usuário logado vê, edita e exclui **todos** os pedidos e vê o histórico completo (não há permissões por pedido ou por cliente).
- Sem verificação de e-mail, sem "esqueci minha senha" (um administrador redefine na tela Usuários) e sem autenticação em dois fatores.
- O cadastro revela se um e-mail já existe ("Já existe uma conta com este e-mail").
- `X-Powered-By: PHP` é enviado pelo próprio PHP; remova com `expose_php=Off`.

## Manutenção

```bash
docker compose exec app composer audit      # vulnerabilidades do PHP
cd src && npm audit                         # vulnerabilidades do front
docker compose exec app composer update     # atualiza dentro das versões permitidas (rode os testes depois)
docker compose exec app php artisan seguranca:verificar
```
