# Histórico de alterações

Registra **quem** mudou **o quê** e **quando** nos pedidos e itens. Todos os usuários logados veem o histórico.

## Onde ver

- Aba **Histórico** (menu do topo): todas as alterações, mais recentes primeiro, com filtros por busca (pedido, item ou campo), quem, o quê
  (criações, edições, exclusões) e período. Os resultados são páginas de 20 **salvamentos**.
- Tela do pedido, seção recolhível **Histórico deste pedido** (as últimas alterações dele) e o link "Ver tudo".

Cada bloco mostra data e hora, o usuário, o pedido (com link) e a lista do que mudou, no formato
`Campo: valor anterior → valor novo` (ex.: `Status: Andamento → Finalizado`). Alterações em itens trazem o item
(`Item #10 Caixa · Solda: (vazio) → 12/03/2026`). Alterações feitas **juntas** (um mesmo salvamento) ficam no mesmo bloco.

## O que é registrado

| Evento | Como aparece |
|---|---|
| Pedido criado | uma linha ("Criou o pedido — 3 item(ns)") com a origem: **Importado do PDF** ou **Criado manualmente**; os itens não geram linhas separadas |
| Dados do pedido editados (número, data, cliente, fornecedor, valor) | uma linha por campo alterado |
| Condições, observações e blocos de endereço (modais) | uma linha por campo/bloco alterado, com o rótulo da tela |
| Item alterado (formulário, "Editar controle" ou status) | uma linha por campo alterado: descrição, quantidade, data de entrega, status, responsável, etapas... |
| Item criado ou removido no formulário | "Criou/Excluiu o item ..." |
| Status do pedido (todos os itens) | uma linha por item cujo status mudou |
| Pedido excluído | "Excluiu o pedido" (o registro continua legível depois) |
| Comandos (`pedidos:reextrair`) | usuário **Sistema**, origem "Reextração do PDF (comando)" |

Só entra o que **realmente mudou** (salvar sem alterar nada não gera registro; `100` → `100.00` também não). Datas aparecem como dd/mm/aaaa,
números sem zeros à direita e o status pelo nome.

Não entram: login/logout e a gestão de usuários.

## Como funciona

- Tabela `historico`: uma linha por campo alterado, com `lote` (agrupa o salvamento), `user_id` e **cópias** do nome do usuário, do número
  do pedido e da descrição do item. Por isso o registro continua legível se o usuário, o pedido ou o item forem apagados
  (não há chave estrangeira para pedido/item; o `user_id` vira nulo se o usuário for removido).
- `HistoricoService` grava e agrupa; `PedidoObserver` e `PedidoItemObserver` (registrados em `AppServiceProvider`) pegam as alterações feitas
  pelo Eloquent, e `PedidoService`/`PedidoUploadService` registram a criação do pedido. O middleware `IniciarLoteDoHistorico` abre um
  lote novo a cada requisição.
- **Atenção a atualizações em massa:** `$pedido->itens()->update([...])` e `delete()` direto na consulta **não** disparam os observers.
  Para entrar no histórico, atualize os itens um a um (como `ControleController::atualizarStatusPedido`).
- O formulário do pedido agora **atualiza os itens pelo `id`** (antes apagava e recriava todos): isso preserva o id e faz o histórico
  mostrar só o que mudou.

## Como estender

Para um campo novo aparecer no histórico, inclua-o em `CAMPOS_PEDIDO`/`CAMPOS_ITEM` (rótulo) em `HistoricoService`
(etapas do controle já entram por `PedidoItem::ETAPAS`).

## Retenção

O histórico não é apagado automaticamente e cresce com o uso (uma linha por campo alterado). Se ficar muito grande, uma rotina de
limpeza de registros antigos pode ser adicionada.
