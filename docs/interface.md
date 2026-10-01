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
│   ├── pedidos/               # componentes do domínio: UploadCard, Filtros, ItemCard, ItemFormCard, EditarSecao, ExcluirPedido
│   ├── Field.tsx              # rótulo + campo + erro (padrão de todos os formulários)
│   ├── ConfirmDialog.tsx      # modal de confirmação
│   └── Paginacao.tsx
├── layouts/AppLayout.tsx      # barra superior + mensagem de sucesso
├── lib/format.ts              # formatação pt-BR (moeda, data, cnpj...) — devolve "—" quando vazio
├── lib/routes.ts              # URLs da aplicação (espelham routes/web.php)
└── types/index.ts             # tipos Pedido, Item, DadosExtras, Paginador...
```

O nome da página (`Pedidos/Show`) é o que o controller passa a `Inertia::render` e corresponde ao arquivo em `pages/`.
O título da aba vem de `<Head title="...">` em cada página.

## Paleta

Definida em `resources/css/app.css` (variáveis do shadcn); troque as cores só ali.

- **Base neutra** (zinc): fundo, cards, bordas e textos. `--primary` é o grafite dos botões e faixas.
- **Destaques**, com moderação: laranja `--brand` `#F56218` (linha do topo, "Processar PDF", borda dos itens do formulário),
  laranja claro `--brand-light` (valor total) e verde-sálvia `--sage` (badge do item, alerta de sucesso).
- `--destructive` (`#A63A0B`) para exclusão e erros; `--ring` (laranja claro) no foco dos campos.
- Use sempre as classes semânticas (`bg-primary`, `text-muted-foreground`, `border-border`...), nunca hexadecimal solto.

## Telas

- **Index** — `UploadCard` (envia o PDF), `Filtros` (GET com os filtros), tabela e `Paginacao`.
- **Show** — resumo, 4 blocos de endereço, condições, observações, conferência de totais (só aparece se divergir),
  itens (`ItemCard`) e texto extraído. Cada seção tem "Editar", que abre `EditarSecao` (modal com `PATCH /pedidos/{id}/dados`).
- **Form** — criar e editar com os itens (`ItemFormCard`: adicionar, remover e minimizar). Itens são opcionais.

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
