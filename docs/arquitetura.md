# Arquitetura

Laravel 12 + PostgreSQL. Interface em Blade com CSS e JavaScript puros (sem framework JS), compilados pelo Vite.

## Camadas

| Camada | Onde | Responsabilidade |
|--------|------|------------------|
| Rotas | `routes/web.php` | `Route::resource` + upload, PDF original e edição por seção |
| Controller | `PedidoController` | Só recebe a requisição, chama um service e devolve a resposta |
| Requests | `app/Http/Requests` | Validação e mensagens em português. Todos herdam de `BaseRequest` |
| Services | `app/Services` | Regras de negócio (veja abaixo) |
| Models | `Pedido`, `PedidoItem` | Relacionamentos, casts, busca (`scopeSearch`) e constantes compartilhadas |
| Views | `resources/views` | Blade; formatação sempre via `Formatar` |

### Services

- **`PdfPedidoParser`** — recebe o texto do PDF e devolve `['pedido', 'itens', 'dados_extras']`. Não toca no banco.
- **`PedidoUploadService`** — lê o PDF (smalot/pdfparser), guarda o arquivo em `storage/app/private/pdfs`,
  chama o parser e cria pedido + itens numa transação. Se o banco falhar, apaga o PDF guardado.
- **`PedidoService`**
  - `salvar()`: grava o pedido e **substitui** os itens pelos enviados (aceita zero itens).
  - `atualizarSecao()`: edição dos modais (`resumo`, `condicoes`, `observacoes` ou um bloco de endereço)
    sem tocar nos itens. Mantém cliente/fornecedor coerentes com o nome dos blocos Faturamento/Fornecedor.

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
`desconto_absoluto`, `icms_monofasico`, `reducao_base_icms`, `base_inss`
(lista única em `PedidoItem::CAMPOS_EXTRAS`).

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
| GET | `/` | lista com filtros e paginação |
| POST | `/upload` | importa um PDF |
| GET | `/pedidos/create`, POST `/pedidos` | criar manualmente |
| GET | `/pedidos/{pedido}` | detalhes |
| GET | `/pedidos/{pedido}/edit`, PUT `/pedidos/{pedido}` | formulário completo (com itens) |
| PATCH | `/pedidos/{pedido}/dados` | edição por seção (modais) |
| GET | `/pedidos/{pedido}/pdf` | PDF original |
| DELETE | `/pedidos/{pedido}` | excluir |

## Decisões de desempenho

- A lista seleciona só as colunas que exibe e usa `withCount('itens')`: não carrega `texto_bruto` nem os itens.
- A busca por número, cliente e fornecedor usa `LIKE`; com muitos pedidos, considere índices.
- Sem axios no front: o JavaScript carregado em todas as telas tem ~1 KB.

## Testes

`php artisan test` roda em SQLite em memória. Cobrem o parser, o CRUD, a edição por seção, a validação,
a busca/paginação e a presença da confirmação de exclusão.
