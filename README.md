# PDF Transformer — Importador de Pedidos

Aplicação **Laravel + React** (Inertia, TypeScript, Tailwind e shadcn/ui) que **importa pedidos de compra / prestação de serviço em PDF**, extrai os dados
(cabeçalho, endereços, condições, itens e impostos) e mostra tudo de forma organizada para o usuário
**conferir e corrigir**.

- Envie um PDF → o sistema lê o texto, cria o pedido e guarda o PDF original.
- A tela de detalhes mostra o que foi extraído, avisa quando os totais não batem e abre o PDF original.
- A tela **Controle** reproduz a planilha CONTROLE DE PEDIDOS (um item por linha, abas por ano, mesmas colunas e cores) e **exporta para Excel** no mesmo formato. Clicar em uma linha abre o pedido, onde o controle de produção é editado.
- Cabeçalho e blocos de endereço são editáveis por **modais** na própria tela; os itens, pelo formulário.
- Pedidos também podem ser criados manualmente.

## Subir o projeto

Requisitos: Docker + Docker Compose e Node.js 20+ (para compilar o front React).

```bash
cp src/.env.example src/.env                       # 1. ambiente (já configurado para o Docker)
docker compose up -d --build                       # 2. app + PostgreSQL + pgAdmin
docker compose exec app composer install           # 3. dependências PHP (primeira vez)
docker compose exec app php artisan key:generate   #    (primeira vez)
docker compose exec app php artisan migrate        # 4. tabelas
cd src && npm install && npm run build             # 5. front React (rode de novo ao mudar resources/js ou resources/css)
```

| Serviço    | Endereço                | Observação                                  |
|------------|-------------------------|---------------------------------------------|
| Aplicação  | http://localhost:8000   |                                             |
| pgAdmin    | http://localhost:5050   | login e senha no `docker-compose.yml`       |
| PostgreSQL | `localhost:5432`        | banco/usuário `laravel`, senha `secret`     |

## Como usar

1. Na tela inicial, escolha o PDF e clique em **Processar PDF** (ou **Criar pedido manual**).
2. Na tela do pedido confira os dados. Use **Abrir PDF original** para comparar lado a lado.
3. Corrija o que for preciso: **Editar** em cada seção (modal) ou **Editar** no topo (itens).
4. Na lista, filtre por número, cliente, fornecedor, data e valor.
5. Em **Controle**, acompanhe a produção item a item (etapas, responsável e status), clique numa linha para abrir o pedido e editar o controle (**Editar controle** em cada item) e use **Exportar** para gerar a planilha. Detalhes em [docs/controle-planilha.md](docs/controle-planilha.md).

## Comandos úteis

```bash
docker compose exec app php artisan test              # testes automatizados
docker compose exec app vendor/bin/pint               # padroniza o estilo do código PHP
docker compose exec app php artisan pedidos:reextrair # reaplica o parser em pedidos já importados
docker compose logs -f app                            # logs
cd src && npm run dev                                 # front com recarregamento automático (em vez de build)
cd src && npm run typecheck                           # confere os tipos TypeScript
cd src && npm audit                                   # vulnerabilidades das dependências do front
docker compose down                                   # parar tudo (os dados do banco ficam no volume)
```

`pedidos:reextrair` só preenche campos **vazios**: nunca sobrescreve o que foi editado à mão.
Útil depois de melhorar o parser (veja [docs/parser-pdf.md](docs/parser-pdf.md)).

## Estrutura

```
src/
├── app/
│   ├── Http/Controllers/PedidoController.php   # fino: recebe a requisição, delega e devolve uma página Inertia
│   ├── Http/Requests/                          # validação (BaseRequest + um por ação)
│   ├── Http/Resources/                         # Pedido/PedidoItem → JSON enviado ao React
│   ├── Http/Middleware/HandleInertiaRequests   # props globais (mensagem de sucesso)
│   ├── Services/
│   │   ├── PdfPedidoParser.php                 # texto do PDF → dados estruturados
│   │   ├── PedidoUploadService.php             # upload: lê, guarda o PDF e cria o pedido
│   │   ├── ControleService.php                 # consultas da planilha de controle (por ano, filtros)
│   │   ├── PlanilhaControleExporter.php        # gera o .xlsx no formato da planilha
│   │   └── PedidoService.php                   # salvar pedido/itens e editar por seção
│   ├── Support/ColunasControle.php             # colunas, títulos, larguras e cores da planilha
│   ├── Models/ (Pedido, PedidoItem)            # constantes BLOCOS, CONDICOES, CAMPOS_EXTRAS, CAMPOS_CONTROLE
│   └── Console/Commands/ReextrairDadosPedidos.php
├── resources/
│   ├── views/app.blade.php                     # única view Blade (casca da aplicação React)
│   ├── css/app.css                             # Tailwind + paleta
│   └── js/                                     # React: pages/, components/, layouts/, lib/, types/
├── routes/web.php
└── tests/                                      # Unit (parser) e Feature (CRUD, edição, busca, props Inertia)
docs/                                           # documentação detalhada
```

## Documentação

- [docs/arquitetura.md](docs/arquitetura.md) — camadas, fluxo do upload, banco de dados e rotas
- [docs/parser-pdf.md](docs/parser-pdf.md) — o que é extraído do PDF e como estender o parser
- [docs/controle-planilha.md](docs/controle-planilha.md) — a planilha de controle: colunas, cores, tela e exportação
- [docs/interface.md](docs/interface.md) — front React: páginas, componentes, paleta e como estender

## Observações

- A exportação usa PhpSpreadsheet, que precisa da extensão PHP `zip`. Ela está no `Dockerfile` (`libzip-dev` + `docker-php-ext-install zip`): depois de atualizar, rode `docker compose up -d --build` uma vez.
- O `docker-compose.yml` monta `./src` como volume: alterações no PHP valem na hora, sem rebuild. No front, rode `npm run dev` (ou `npm run build`).
  Só é preciso `--build` ao mudar o `Dockerfile`.
- O Xdebug vem ativo na imagem (`start_with_request=yes`). Se notar lentidão e não estiver depurando,
  mude para `trigger` no `Dockerfile`.
- Ainda não há login: qualquer pessoa com acesso à URL vê e edita os pedidos.
