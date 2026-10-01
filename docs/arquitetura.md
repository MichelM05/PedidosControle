# Arquitetura

Laravel 12 + PostgreSQL no back; **React 19 + TypeScript** no front, ligados pelo **Inertia**: o Laravel continua
dono das rotas, validação e sessão, e cada ação do controller devolve uma página React com suas props
(sem API REST separada). O Vite compila o front.

## Camadas

| Camada | Onde | Responsabilidade |
|--------|------|------------------|
| Rotas | `routes/web.php` | `Route::resource` + upload, PDF original e edição por seção |
| Controller | `PedidoController` | Só recebe a requisição, chama um service e devolve `Inertia::render(...)` ou um redirect |
| Requests | `app/Http/Requests` | Validação e mensagens em português. Todos herdam de `BaseRequest` |
| Services | `app/Services` | Regras de negócio (veja abaixo) |
| Models | `Pedido`, `PedidoItem` | Relacionamentos, casts, busca (`scopeSearch`) e constantes compartilhadas |
| Resources | `app/Http/Resources` | Transformam `Pedido`/`PedidoItem` no JSON enviado ao React (campos pesados só quando carregados) |
| Front | `resources/js` | Páginas e componentes React (veja [interface.md](interface.md)); formatação em `lib/format.ts` |

### Services

- **`PdfPedidoParser`** — recebe o texto do PDF e devolve `['pedido', 'itens', 'dados_extras']`. Não toca no banco.
- **`PedidoUploadService`** — lê o PDF (smalot/pdfparser), guarda o arquivo em `storage/app/private/pdfs`,
  chama o parser e cria pedido + itens numa transação. Se o banco falhar, apaga o PDF guardado.
- **`ControleService`** / **`PlanilhaControleExporter`** — consultas e geração do `.xlsx` da planilha de controle (veja [controle-planilha.md](controle-planilha.md)).
- **`HistoricoService`** — registra e agrupa o histórico de alterações (veja [historico.md](historico.md)).
- **`PedidoService`**
  - `salvar()`: grava o pedido e **sincroniza os itens pelo `id`** (atualiza os existentes, cria os sem id e remove os que não vieram; aceita zero itens).
  - `atualizarSecao()`: edição dos modais (`resumo`, `condicoes`, `observacoes` ou um bloco de endereço)
    sem tocar nos itens. Mantém cliente/fornecedor coerentes com o nome dos blocos Faturamento/Fornecedor.

## Como o Inertia liga back e front

- `GET` → o controller devolve `Inertia::render('Pedidos/Show', [...props])`; o React renderiza `resources/js/pages/Pedidos/Show.tsx`.
- Formulários usam `useForm` e enviam por `POST/PUT/PATCH/DELETE`; o Laravel valida e responde com `redirect()`,
  e os erros voltam em `errors` (campos de itens como `itens.0.qtd`).
- A mensagem de sucesso (`->with('success', ...)`) chega em `flash.success` (`HandleInertiaRequests`).
- Só existe uma view Blade, `resources/views/app.blade.php`.

## Fluxo do upload

```
PDF ──► UploadPedidoRequest (PDF, até 10 MB)
    ──► PedidoUploadService ─► PdfPedidoParser.extrair(texto)
                             ─► guarda o arquivo + cria Pedido e PedidoItem (transação)
    ──► redireciona para a tela de detalhes
```

## Banco de dados

**`pedidos`**: `numero`, `data_pedido`, `cliente`, `fornecedor`, `valor`, `texto_bruto` (texto completo do PDF),
`arquivo_pdf` (caminho do original) e `dados_extras` (JSON, abaixo).

**`pedido_itens`** (`pedido_id`, apaga em cascata): `item`, `material`, `denominacao`, `qtd`, `un`, `preco`,
`vlr_tot`, `icms`, `ipi` e os campos extras `dt_entrega`, `item_lei`, `tipo_manutencao`, `local_prestacao`,
`desconto_absoluto`, `icms_monofasico`, `reducao_base_icms`, `base_inss`, `cidade_entrega`
(lista única em `PedidoItem::CAMPOS_EXTRAS`) e o **controle de produção** preenchido pela equipe (`PedidoItem::CAMPOS_CONTROLE`):
`desenho_nesting`, `compra_mp`, `compra_insumo`, `usinagem`, `corte_dobra`, `solda`, `pintura`, `montagem`, `responsavel` e `status`.

**`dados_extras`** guarda o cabeçalho do PDF que não precisa de coluna própria (não é filtrado nem ordenado):

```json
{
  "cond_pgto": "Pagamento em 30 dias", "frete": "CIF", "moeda": "Real",
  "comprador": "...", "contato_nome": "...", "contato_email": "...",
  "total_icms": "0.00", "total_ipi": "0.00", "total_produtos": "984000.00",
  "observacoes": "texto livre",
  "blocos": {
    "fornecedor":  {"titulo": "Fornecedor", "nome": "...", "endereco": ["linha 1", "linha 2"], "cnpj": "...", "ie": "...", "fone": "..."},
    "faturamento": {}, "local": {}, "cobranca": {}
  }
}
```

Chaves e rótulos ficam em `Pedido::CONDICOES` e `Pedido::BLOCOS`; views, modais e validação leem dessas constantes.

## Rotas

| Método | URL | Ação |
|--------|-----|------|
| GET/POST | `/login`, POST `/logout` | entrar e sair (veja [autenticacao.md](autenticacao.md)); todas as rotas abaixo exigem login |
| GET/PATCH/PUT | `/perfil`, `/perfil/senha` | o usuário troca nome, e-mail e senha |
| GET/POST/PATCH | `/usuarios` | gestão de usuários (administradores) |
| GET | `/historico` | histórico de alterações com filtros (`Historico/Index`) |
| GET | `/` | lista com filtros e paginação (`Pedidos/Index`) |
| POST | `/upload` | importa um PDF |
| GET | `/pedidos/create`, POST `/pedidos` | criar manualmente |
| GET | `/pedidos/{pedido}` | detalhes |
| GET | `/pedidos/{pedido}/edit`, PUT `/pedidos/{pedido}` | formulário completo (com itens) |
| PATCH | `/pedidos/{pedido}/dados` | edição por seção (modais) |
| GET | `/pedidos/{pedido}/pdf` | PDF original |
| DELETE | `/pedidos/{pedido}` | excluir |
| GET | `/controle` | planilha de controle na web (`Controle/Index`) |
| PATCH | `/controle/itens/{item}` | salva o controle de produção de um item (modal da tela do pedido) |
| GET | `/controle/exportar` (`?ano=`) | baixa o .xlsx (todos os anos ou um) |
| PATCH | `/pedidos/{pedido}/status` | muda o status de todos os itens do pedido |

## Decisões de desempenho

- A lista seleciona só as colunas que exibe e usa `withCount('itens')`: não carrega `texto_bruto` nem os itens.
- A busca por número, cliente e fornecedor usa `LIKE`; com muitos pedidos, considere índices.
- O front compilado tem ~460 KB (145 KB gzip), todas as páginas num único arquivo: adequado para um app interno desse tamanho.

## Testes

`php artisan test` roda em SQLite em memória. Cobrem o parser, o CRUD, a edição por seção, a validação,
a busca/paginação, as props enviadas ao React (`assertInertia`) e a mensagem de sucesso compartilhada.
