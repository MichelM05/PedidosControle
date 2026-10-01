# Segurança

Resultado da última verificação e o que fazer **antes de publicar** o sistema.

## O que já está protegido

- **Dependências:** `composer audit` e `npm audit` sem vulnerabilidades (verificar de tempos em tempos; veja "Manutenção").
- **Login:** senhas com hash (bcrypt), sessão renovada ao entrar, mensagem única para e-mail/senha errados e conta desativada,
  bloqueio por 15 minutos depois de 5 senhas erradas (por e-mail + IP) e limite de 10 cadastros por hora por IP.
- **CSRF:** todas as requisições que alteram dados exigem token (sem token a resposta é 419).
- **Acesso:** todas as rotas exigem login; `/usuarios` exige administrador (403 para os demais); usuário desativado é desconectado.
- **Injeção:** consultas com parâmetros (sem SQL montado com texto do usuário); o React escapa tudo que mostra (sem `dangerouslySetInnerHTML`);
  células do Excel exportado são gravadas como texto (valores que começam com `=` não viram fórmula).
- **Upload:** só PDF (verificado pelo conteúdo, não só pela extensão), até 10 MB, guardado com nome aleatório em `storage/app/private`
  (fora da pasta pública) e entregue apenas por rota autenticada.
- **Cabeçalhos:** `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`, `Permissions-Policy`,
  `Content-Security-Policy` restritiva nas páginas (`CabecalhosDeSeguranca`) e `Strict-Transport-Security` em HTTPS.
- **Mass assignment:** os dados vêm sempre de requests validados (`validated()`); campos como `is_admin` só são aceitos na tela de administrador.
- **Segredos:** `.env` fora do Git e nenhuma senha ou chave no histórico.

## Checklist antes de publicar

1. **`src/.env` de produção** (o de desenvolvimento tem `APP_DEBUG=true`, que mostra variáveis e senhas em páginas de erro):
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://seu-dominio
   LOG_LEVEL=warning
   SESSION_SECURE_COOKIE=true
   SESSION_ENCRYPT=true
   REGISTRO_ABERTO=false      # depois de criar as contas da equipe
   ```
   Gere uma `APP_KEY` própria (`php artisan key:generate`) e uma senha forte para o banco.
2. **HTTPS obrigatório**, atrás de um proxy (nginx, Caddy, Traefik). Se houver proxy, configure `trustProxies` em `bootstrap/app.php`
   para o Laravel reconhecer o HTTPS (senão o HSTS e o cookie seguro não funcionam).
3. **Não use `php artisan serve` em produção** (é só para desenvolvimento): use PHP-FPM + nginx.
4. **Docker:** o `docker-compose.yml` de desenvolvimento publica o PostgreSQL (`5432`) e o pgAdmin (`5050`) com a senha `secret`. Em servidor,
   não exponha essas portas (use `127.0.0.1:5432:5432` ou remova) e troque as senhas. Remova também o Xdebug da imagem e ative `expose_php=Off`.
5. **Cadastro aberto:** com `REGISTRO_ABERTO=true`, qualquer pessoa que alcance a URL cria uma conta e vê todos os pedidos.
   Feche o cadastro assim que a equipe tiver contas (administradores criam usuários na tela **Usuários**).
6. **Produção do código:** `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`,
   `php artisan config:cache route:cache view:cache` e `php artisan migrate --force`.
7. **Backup** do banco e de `storage/app/private/pdfs` (os PDFs guardam dados de clientes e fornecedores: nomes, e-mails, telefones, CNPJs;
   considere a LGPD).

## Limitações conhecidas (decisões de produto)

- Todo usuário logado vê, edita e exclui **todos** os pedidos (não há permissões por pedido ou por cliente).
- Sem verificação de e-mail e sem "esqueci minha senha" (um administrador redefine na tela Usuários); sem autenticação em dois fatores.
- O cadastro revela se um e-mail já existe ("Já existe uma conta com este e-mail").
- `X-Powered-By: PHP` é enviado pelo próprio PHP; remova com `expose_php=Off` no `php.ini` (ou no proxy).

## Manutenção

```bash
docker compose exec app composer audit      # vulnerabilidades do PHP
cd src && npm audit                         # vulnerabilidades do front
docker compose exec app composer update     # atualiza dentro das versões permitidas (rode os testes depois)
```
