# Login e usuários

Todas as telas exigem login (sessão do Laravel). Quem não entrou é enviado para `/login` e, depois de entrar, volta para onde queria ir.

## Contas e primeiro acesso

**Não há cadastro público**: a tela de login só tem e-mail e senha, e `/registro` não existe (404). As contas são criadas por um administrador.

- **Primeiro administrador:** por comando, no servidor (no Laravel Cloud, na aba **Commands** do ambiente):
  ```bash
  docker compose exec app php artisan usuarios:criar seu@email.com --nome="Seu Nome" --admin
  # a senha é perguntada (mínimo 8 caracteres, com letras e números); também aceita --senha=...
  ```
- **Demais usuários:** o administrador entra, clica em **Usuários** (menu do topo) e em **+ Novo usuário**, e informa nome, e-mail e senha inicial.
  A pessoa pode trocar a senha em **Meu perfil**.
- Sem `--admin` o comando cria um usuário comum.

## O que existe

| Tela / rota | Quem acessa | O que faz |
|---|---|---|
| `/login` | visitante | e-mail + senha, com "Lembrar-me neste computador" |
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
- Front: `pages/Auth/Login.tsx` (com `GuestLayout`), `pages/Perfil/Edit.tsx`, `pages/Usuarios/Index.tsx`; o menu do usuário e o botão Sair
  estão em `layouts/AppLayout.tsx`.
- Testes: `tests/Feature/AuthTest.php`. Os demais testes de feature entram com `actingAs(User::factory()->create())`.

## Não incluído (ainda)

- **Bloqueio por tentativas erradas** (rate limit no login) e **"esqueci minha senha"** por e-mail (o projeto não tem envio de e-mail configurado).
  Um administrador pode definir uma nova senha para o usuário na tela Usuários.
- Níveis de permissão além de "administrador" / "usuário": hoje todo usuário logado vê e edita todos os pedidos.
