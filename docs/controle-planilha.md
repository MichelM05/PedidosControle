# Controle de pedidos (planilha)

O sistema reproduz a planilha interna **CONTROLE DE PEDIDOS**: uma linha por **item de pedido**, uma aba por ano.
A tela **Controle** mostra essa planilha na web (somente leitura) e a exportação gera o `.xlsx` no mesmo formato. A edição é feita na tela do pedido.

## Colunas

Definidas em um só lugar, `app/Support/ColunasControle.php`, usado pela tela e pela exportação.

| Coluna da planilha | De onde vem | Observação |
|---|---|---|
| PEDIDO *(oculta)* | — | sem equivalente no sistema; sai vazia e oculta |
| CLIENTE *(oculta)* | cliente do pedido | oculta, como na planilha |
| O.C CLIENTE | número do pedido (do PDF) | número quando só tem dígitos; texto se não ("verbal") |
| DESCRIÇÃO PRODUTO | denominação do item | edite no formulário do pedido |
| QUANT. | quantidade do item | edite no formulário do pedido |
| DATA DE ENTREGA | `Dt. Entrega` do item | editável em "Editar controle" |
| CIDADE ENTREGA | local da prestação sem a UF | preenchida na importação ("Ponta Grossa PR" → "Ponta Grossa"); editável em "Editar controle" |
| DESENHO NESTING, COMPRA M.P, COMPRA INSUMO, USINAGEM, CORTE E/OU DOBRA, SOLDA, PINTURA, MONTAGEM | **controle interno** (não vem do PDF) | texto livre ou data: "recebido 02/09", "12/03/2026", "xxxxx" |
| RESPONSÁVEL | controle interno | texto |
| STATUS | controle interno | Andamento, Finalizado, Entregue ou Cancelado (padrão: Andamento) |

Os campos de controle ficam em `pedido_itens` (`PedidoItem::CAMPOS_CONTROLE`). O parser e o `pedidos:reextrair`
**não tocam neles**, então reimportar ou reextrair nunca apaga o que a equipe preencheu.

## Status e cores

Status do item: **Andamento**, **Finalizado**, **Entregue** e **Cancelado** (`PedidoItem::STATUS`). Na tela do pedido dá para mudar o
status de um item (seletor no item) ou de **todos os itens de uma vez** ("Status do pedido").

As mesmas cores valem na grade de Controle, na lista de pedidos e no `.xlsx` (constantes em `App\Support\ColunasControle`
e `resources/js/lib/controle.ts`; mantenha os dois iguais):

| Situação | Cor | Estilo |
|---|---|---|
| Cancelado | lilás `#D9C7EA` | texto riscado |
| Entregue | verde `#A9D18E` | texto riscado |
| Finalizado | azul claro `#BDD7EE` | — |
| Em andamento, entrega em **até 10 dias** (ou já vencida) | vermelho `#FF9999` | — |
| Em andamento, entrega em **até 20 dias** | amarelo `#FFD965` | — |
| Em andamento, entrega em mais de 20 dias (ou sem data) | cinza `#ECECEC` | — |

A prioridade é a da tabela (de cima para baixo). O prazo é contado a partir de **hoje** (`=TODAY()` na célula Q1 do Excel).

**Na lista de pedidos** (tela inicial) cada pedido recebe uma cor pela situação geral dos itens: cancelado se todos foram cancelados,
entregue se todos estão encerrados (entregues ou cancelados), finalizado se nenhum está em andamento e, caso contrário, andamento,
com o prazo da **entrega mais próxima entre os itens em andamento**.

Cabeçalho da planilha: cinza `#E7E6E6` (colunas até Desenho nesting), amarelo `#FEF2CB` (compras), verde `#E2EFD9` (produção e responsável)
e azul-acinzentado `#D6DCE4` (status).

**Diferenças em relação à planilha original**, de propósito: a regra do prazo era amarela em 7 dias, tinha prioridade sobre "Entregue"
e usava uma data digitada em Q1 (itens já entregues também ficavam amarelos); aqui a situação vem primeiro e Q1 é `=TODAY()`.
Linhas sem data de entrega não ganham cor de prazo. Entregue passou de azul-acinzentado para verde, e Finalizado de verde para azul claro.

## Tela Controle (`/controle`)

Consulta, no formato da planilha. **Não edita**: clicar (ou Enter) em uma linha abre o pedido.

- Duas visões (botão ao lado dos filtros): **Por pedido** (padrão) e **Planilha (por item)**, a grade abaixo. A exportação `.xlsx` é sempre por item.
- **Por pedido**: uma linha por pedido com número, cliente, quantidade de itens (e quantos em cada status), próxima entrega, cidades, responsáveis e
  a situação geral, com a cor da lista de pedidos. O botão ▸ (ou clicar na linha) expande os itens de forma resumida: posição, descrição, quantidade,
  entrega, cidade, responsável e status. Os filtros valem para os itens mostrados ("2 de 5 itens"), mas a situação e as
  contagens do pedido consideram todos os itens. Os pedidos vêm ordenados pela entrega mais próxima.
- Abas por ano (ano do **pedido**; sem data do pedido, vale a data de importação) e contagem por status.
- Filtros: busca (pedido, cliente, produto, cidade), status, responsável, "Ocultar entregues e cancelados" e "Mostrar colunas ocultas".

## Editar o controle (tela do pedido)

Na tela do pedido, cada item tem o bloco **Controle de produção** com status, responsável, cidade de entrega e as 8 etapas.
O botão **Editar controle** abre um modal com esses campos e a data de entrega (`PATCH /controle/itens/{item}`, que só aceita
esses campos). Os mesmos campos também estão no formulário de edição do pedido (seção "Controle de produção").
Descrição, quantidade, número e cliente são editados no pedido (formulário ou modais da tela).

## Exportar para Excel

| Onde | O que gera | Arquivo |
|---|---|---|
| Controle → "Exportar aba 2026" | itens do ano da aba | `controle-de-pedidos-2026.xlsx` |
| Controle → "Exportar todos os anos" | uma aba por ano | `controle-de-pedidos.xlsx` |

O seletor ao lado dos botões escolhe o conteúdo: **Exportar com itens** (a planilha acima, um item por linha) ou **Exportar só pedidos**
(`PlanilhaPedidosExporter`, `?modo=pedidos`): uma linha por pedido, sem os itens, com O.C, cliente, data, quantidade de itens e quantos em cada
status, valor total, próxima entrega, cidades, responsáveis e status geral. A linha é pintada pela situação geral e pelo prazo da próxima entrega
(cores fixas, não regras do Excel), há filtro automático e cabeçalho congelado. Arquivos: `pedidos.xlsx` e `pedidos-2026.xlsx`.

O `.xlsx` (`PlanilhaControleExporter`, PhpSpreadsheet) segue o modelo: título "CONTROLE PEDIDOS" em F1 e a data em Q1,
cabeçalho com as mesmas cores e larguras, colunas A e B ocultas, linhas em cinza com bordas, zoom de 85% e sem linhas de grade,
regras de cor reais do Excel (cancelado, entregue, finalizado, urgente e alerta; dá para editar a planilha e as cores continuam funcionando) e linhas formatadas até a 250.
Números, datas e quantidades vão como valores do Excel (dá para somar e filtrar). Etapas que são datas viram data; o resto fica como texto.
O cabeçalho fica congelado (melhoria sobre o modelo).

## Como estender

- **Nova coluna de controle**: migration em `pedido_itens`, chave em `PedidoItem::CAMPOS_CONTROLE` (e `ETAPAS`, se for etapa),
  coluna em `ColunasControle::lista()`, `ControleLinhaResource`, tipos `LinhaControle`/`Item` em `types/index.ts`,
  regra em `AtualizarControleItemRequest` e campos em `EditarControleItem.tsx`, `ItemCard.tsx` e `ItemFormCard.tsx`.
- **Nova cor ou regra**: constantes em `ColunasControle`; a regra do Excel está em `PlanilhaControleExporter::regrasDeCor()`
  e a da tela em `estiloPorSituacao()` (`lib/controle.ts`, usada pela grade e pela lista de pedidos). Mantenha as duas na mesma ordem de prioridade.
