# Interface

Blade + CSS/JS puros, compilados pelo Vite (`npm run build`; em desenvolvimento, `npm run dev`).

## Paleta e tokens

Definidos no `:root` de `resources/css/app.css`; troque as cores só ali.

- **Base neutra** (`--tone-50` … `--tone-900`): fundo, cards, bordas e textos.
- **Destaques**, usados com moderação: laranja `#F56218` (linha do topo, botão "Processar PDF", borda dos itens do formulário),
  laranja claro `#FF9D2E` (valor total, foco dos campos), verde-sálvia `#9DC9AC` (badge do item, alerta de sucesso).
  `--cream` (`#FFFEC7`) e `--olive` (`#919167`) estão definidos, mas ainda sem uso.
- **Semânticas**: `--bg`, `--surface`, `--border`, `--text`, `--muted`, `--accent` (botão primário), `--danger`
  (exclusão e erros), `--radius*` e `--shadow*`.

## CSS e JS por tela

| Arquivo | Carregado em | Conteúdo |
|---------|--------------|----------|
| `css/app.css` | todas | tokens, layout, botões, formulários, tabela, alertas, modal, paginação |
| `css/upload.css`, `js/upload.js` | lista | card de upload do PDF |
| `css/pedidos/show.css` | detalhes | resumo, partes, condições, itens, conferência, botões de edição |
| `css/pedidos/form.css`, `js/pedidos/form.js` | criar/editar | cards de item (adicionar, remover, minimizar) |
| `js/ui.js` | todas | modal de confirmação, modais de edição, bloqueio de envio duplicado |

Cada tela declara seus arquivos com `@push('styles')` / `@push('scripts')` e todos precisam estar em `vite.config.js`.

## Componentes reutilizáveis

- **Confirmação** — qualquer `<form data-confirm="mensagem" data-confirm-title="..." data-confirm-button="...">` abre o modal
  único do layout antes de enviar. Para exclusões use `<x-delete-button :action="..." :message="..." />`.
- **Modais de edição** — `<button data-open-modal="edit-xyz">` abre `<dialog id="edit-xyz">`; `data-close-modal` fecha;
  `data-auto-open` reabre após erro de validação. Gerados em `partials/editar-dados.blade.php`.
- **`Formatar`** (`app/Helpers/Formatar.php`) — `moeda`, `numero`, `preco`, `quantidade`, `data`, `cnpj`, `texto`.
  Sempre devolvem `—` quando não há valor. Nas views, importe com `@use('App\Helpers\Formatar')` (cada partial precisa do seu).
- **Paginação** — `resources/views/pagination/default.blade.php`, definida como padrão no `AppServiceProvider`.
- **Alertas** — sucesso e erros de validação são exibidos pelo layout; não é preciso repetir nas telas.

## Tela de detalhes (`partials/pedido-card` + `partials/detalhes/`)

`resumo` · `partes` (4 blocos de endereço) · `condicoes` (condições + observações) · `conferencia`
(só aparece se o total do PDF diferir da soma dos itens) · `item`. Os modais ficam em `editar-dados`.

## Convenções

- Textos em português, só a primeira letra maiúscula nos botões e rótulos ("Salvar alterações").
- Botões: `.btn` + `.btn-primary` (ação principal), `.btn-secondary`, `.btn-danger` (destrutivo), `.btn-actions` (tabela).
- Cores sempre por variável, nunca hexadecimal solto no CSS ou na view.
