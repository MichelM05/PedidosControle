# Interface (front React)

React 19 + TypeScript, ligado ao Laravel pelo **Inertia**. Estilo com **Tailwind CSS 4** e componentes **shadcn/ui**
(Radix). Compilado pelo Vite: `npm run build` (confere os tipos e gera `public/build`) ou `npm run dev`.

## Estrutura

```
resources/js/
├── app.tsx                    # inicia o Inertia e carrega as páginas
├── pages/Pedidos/             # uma página por tela: Index, Show, Form (criar e editar)
├── components/
│   ├── ui/                    # shadcn (button, card, dialog, table...) — gerados, evite editar
│   ├── controle/              # GradeControle (a grade da planilha, somente leitura)
│   ├── pedidos/               # componentes do domínio: UploadCard, Filtros, ItemCard, ItemFormCard, EditarItem, EditarSecao, EditarControleItem, TituloBloco, ExcluirPedido
│   ├── Field.tsx              # rótulo + campo + erro (padrão de todos os formulários)
│   ├── ConfirmDialog.tsx      # modal de confirmação
│   └── Paginacao.tsx
├── layouts/AppLayout.tsx      # barra superior (nome PedidosControle e abas Pedidos/Controle) + mensagem de sucesso
├── lib/controle.ts            # cores e regras de prazo por situação (grade, lista e legenda)
├── lib/format.ts              # formatação pt-BR (moeda, data, cnpj...) — devolve "—" quando vazio
├── lib/routes.ts              # URLs da aplicação (espelham routes/web.php)
└── types/index.ts             # tipos Pedido, Item, DadosExtras, Paginador...
```

O nome da página (`Pedidos/Show`) é o que o controller passa a `Inertia::render` e corresponde ao arquivo em `pages/`.
O título da aba vem de `<Head title="...">` em cada página.

## Paleta

As cores do projeto, definidas em `resources/css/app.css` (variáveis do shadcn); troque as cores só ali. O **laranja** é a identidade; o resto é
fundo neutro claro, com as demais cores só em detalhes pequenos.

| Cor | Hex | Onde |
|---|---|---|
| Laranja | `#F56218` | barra do topo, botões principais, aba ativa de login — `bg-primary` |
| Laranja claro | `#FF9D2E` | hover dos botões principais, foco dos campos |
| Verde-sálvia | `#9DC9AC` | só detalhes: selos (badges) e borda do alerta de sucesso |
| Oliva | `#919167` | só textos secundários e rótulos (versão escura `#6B6B4A`) |
| Creme | `#FFFEC7` | definido, ainda sem uso de destaque |

Fundo `#F7F7F4` (neutro claro), faixas de destaque (importar pedido, resumo do pedido) em cinza neutro `#ECECE7` (`bg-band`), cartões brancos, texto em oliva muito escuro (`--ink`, `#26261A`), bordas `#E4E2DA` e hover dos botões
secundários em tom claro do laranja (`#FFE2C2`). `--destructive` (`#B91C1C`) é só para excluir e erros. As cores de situação/prazo da planilha
estão em `docs/controle-planilha.md`.
Use sempre as classes semânticas (`bg-primary`, `bg-band`, `text-muted-foreground`, `border-border`...), nunca hexadecimal solto.

## Telas

- **Index** — "Importar pedido" (`UploadCard`), `Filtros` (GET com os filtros) e a tabela **Pedidos cadastrados**, com cada linha colorida pela situação/prazo (`lib/controle.ts`) e `LegendaCores`.
- **Controle** — a planilha na web (somente leitura): abas por ano, filtros, grade (`GradeControle`) e exportação; clicar em uma linha abre o pedido. Veja [controle-planilha.md](controle-planilha.md).
- **Show** — resumo, 4 blocos de endereço, condições, observações, conferência de totais (só aparece se divergir),
  itens (`ItemCard`) e texto extraído. Cada item tem faixa lateral e pílula de status na cor da situação (a mesma da grade de controle), material e entrega sempre à vista,
  observações e controle de produção recolhidos, "Editar item" (modal `EditarItem`, `PATCH /itens/{id}`) e minimizar; há "Minimizar todos". Cada seção tem "Editar", que abre `EditarSecao` (modal com `PATCH /pedidos/{id}/dados`).
- **Form** — criar e editar com os itens (`ItemFormCard`: adicionar, remover e minimizar; ao editar, todos começam minimizados). Itens são opcionais.
  Os campos do item (`SecoesDoItem`) seguem a ordem da visualização e são compartilhados com o modal `EditarItem`.

## Como estender

- **Nova tela**: crie `pages/<Pasta>/<Nome>.tsx`, a rota e um `Inertia::render('<Pasta>/<Nome>', props)` no controller.
- **Novo componente shadcn**: `npx shadcn@latest add <nome>` (usa o `components.json`).
- **Nova seção editável no modal**: acrescente os campos em `campos()` e os valores iniciais em `valoresIniciais()` (`EditarSecao.tsx`)
  e trate a seção em `PedidoService::atualizarSecao()` e `AtualizarDadosPedidoRequest`.
- **Confirmação de ação destrutiva**: envolva o botão em `<ConfirmDialog ... onConfirm={...}>` (exemplo: `ExcluirPedido`).

## Convenções

- Textos em português; só a primeira letra maiúscula em botões e rótulos ("Salvar alterações").
- Botões: `variant="default"` (ação principal), `outline` (secundário), `destructive` (excluir).
- Formatação de valores sempre por `lib/format.ts`; nunca `toLocaleString` solto nas telas.
