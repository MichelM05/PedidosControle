# Login e usuários

Todas as telas exigem login (sessão do Laravel). Quem não entrou é enviado para `/login` e, depois de entrar, volta para onde queria ir.

## Criar conta e primeiro acesso

Na tela de login há duas abas: **Entrar** e **Criar conta** (nome, e-mail, senha e confirmação). Ao criar, a pessoa já entra no sistema.

- **O primeiro cadastro do sistema vira administrador** (a tela avisa isso enquanto não há contas). Os seguintes são usuários comuns.
- Administradores também criam e gerenciam contas na tela **Usuários** (menu do topo).
- Para **fechar o cadastro aberto** (só administradores criam contas), defina `REGISTRO_ABERTO=false` no `src/.env` e rode
  `docker compose exec app php artisan config:clear`. A aba "Criar conta" some e `POST /registro` devolve 404.
- Por comando (útil para criar um administrador com o cadastro fechado):
  `docker compose exec app php artisan usuarios:criar seu@email.com --nome="Seu Nome" --admin` (a senha é perguntada).

> Com o cadastro aberto, **qualquer pessoa que alcance a URL do sistema consegue criar uma conta e ver os pedidos**.
> Se o sistema for exposto fora da rede da empresa, feche o cadastro depois de criar as contas.

## O que existe

| Tela / rota | Quem acessa | O que faz |
|---|---|---|
| `/login` | visitante | abas Entrar (e-mail + senha, "Lembrar-me neste computador") e Criar conta |
| `POST /registro` | visitante | cria a conta e entra (se `REGISTRO_ABERTO=true`) |
| `POST /logout` ("Sair" no topo) | logado | encerra a sessão |
| `/perfil` (nome no topo) | logado | troca nome, e-mail e senha (pede a senha atual) |
| `/usuarios` | **administrador** | lista, cria e edita usuários: nome, e-mail, senha, administrador e ativo |

- **Usuários não são apagados**: desative (`ativo` desmarcado). Quem está desativado não consegue entrar e, se estava logado, é
  desconectado na próxima requisição.
- Na edição, deixar a senha em branco mantém a atual.
- O **último administrador ativo** não pode ser desativado nem rebaixado.
- A mensagem de erro do login é a mesma para e-mail inexistente, senha errada e conta desativada (não revela quais e-mails existem).
- Senhas são guardadas com hash (bcrypt); mínimo de 8 caracteres.

## Como funciona

- Tabela `users`: `name`, `email`, `password`, `is_admin`, `ativo`.
- `routes/web.php`: tudo (menos login) está em `middleware(['auth', 'ativo'])`; `/usuarios` ainda passa por `admin`.
- Middlewares: `ApenasAdministradores` (`admin`, devolve 403) e `BloquearUsuarioInativo` (`ativo`); o redirecionamento de visitantes
  e de quem já entrou está em `bootstrap/app.php`.
- O usuário logado chega ao React em `auth.user` (`HandleInertiaRequests`): `id`, `name`, `email`, `is_admin`.
- Front: `pages/Auth/Login.tsx` (com `GuestLayout`; abas Entrar/Criar conta), `pages/Perfil/Edit.tsx`, `pages/Usuarios/Index.tsx`; o menu do usuário e o botão Sair
  estão em `layouts/AppLayout.tsx`.
- Testes: `tests/Feature/AuthTest.php`. Os demais testes de feature entram com `actingAs(User::factory()->create())`.

## Não incluído (ainda)

- **Bloqueio por tentativas erradas** (rate limit no login) e **"esqueci minha senha"** por e-mail (o projeto não tem envio de e-mail configurado).
  Um administrador pode definir uma nova senha para o usuário na tela Usuários.
- Níveis de permissão além de "administrador" / "usuário": hoje todo usuário logado vê e edita todos os pedidos.
