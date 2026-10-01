# Controle de pedidos (planilha)

O sistema reproduz a planilha interna **CONTROLE DE PEDIDOS**: uma linha por **item de pedido**, uma aba por ano.
A tela **Controle** mostra essa planilha na web (editável) e a exportação gera o `.xlsx` no mesmo formato.

## Colunas

Definidas em um só lugar, `app/Support/ColunasControle.php`, usado pela tela e pela exportação.

| Coluna da planilha | De onde vem | Observação |
|---|---|---|
| PEDIDO *(oculta)* | — | sem equivalente no sistema; sai vazia e oculta |
| CLIENTE *(oculta)* | cliente do pedido | oculta, como na planilha |
| O.C CLIENTE | número do pedido (do PDF) | número quando só tem dígitos; texto se não ("verbal") |
| DESCRIÇÃO PRODUTO | denominação do item | editável na grade |
| QUANT. | quantidade do item | editável |
| DATA DE ENTREGA | `Dt. Entrega` do item | editável |
| CIDADE ENTREGA | local da prestação sem a UF | preenchida na importação ("Ponta Grossa PR" → "Ponta Grossa"); editável |
| DESENHO NESTING, COMPRA M.P, COMPRA INSUMO, USINAGEM, CORTE E/OU DOBRA, SOLDA, PINTURA, MONTAGEM | **controle interno** (não vem do PDF) | texto livre ou data: "recebido 02/09", "12/03/2026", "xxxxx" |
| RESPONSÁVEL | controle interno | texto |
| STATUS | controle interno | Andamento, Finalizado ou Entregue (padrão: Andamento) |

Os campos de controle ficam em `pedido_itens` (`PedidoItem::CAMPOS_CONTROLE`). O parser e o `pedidos:reextrair`
**não tocam neles**, então reimportar ou reextrair nunca apaga o que a equipe preencheu.

## Cores das linhas

Mesmas cores da planilha, na tela e no `.xlsx`:

| Situação | Cor | Estilo |
|---|---|---|
| Entregue | azul-acinzentado `#8496B0` | texto riscado |
| Finalizado | verde `#C5E0B3` | — |
| Entrega em até 7 dias da data de hoje (ou atrasada) e ainda em aberto | amarelo `#FFD965` | — |
| Em andamento | cinza `#ECECEC` | — |

Cabeçalho: cinza `#E7E6E6` (colunas até Desenho nesting), amarelo `#FEF2CB` (compras), verde `#E2EFD9` (produção e responsável)
e azul-acinzentado `#D6DCE4` (status). As cores ficam em constantes de `ColunasControle`.

**Duas diferenças em relação à planilha original**, de propósito:

- Na planilha, a regra do prazo tinha prioridade sobre "Entregue" e usava uma data digitada em Q1, então itens já entregues
  também ficavam amarelos. Aqui a ordem é Entregue → Finalizado → Prazo, e a data de referência (Q1) é `=TODAY()`.
- A regra do prazo ignora itens sem data de entrega (na planilha, uma data vazia contava como "vencida").

## Tela Controle (`/controle`)

- Abas por ano (ano do **pedido**; sem data do pedido, vale a data de importação) e contagem por status.
- Filtros: busca (pedido, cliente, produto, cidade), status, responsável, "Ocultar entregues" e "Mostrar colunas ocultas".
- **Editar**: clique na célula, digite e tecle Enter (Esc cancela). Status é um seletor. Salva na hora (`PATCH /controle/itens/{item}`).
  O número do pedido abre o pedido; cliente e número só são editáveis na tela do pedido.
- Os mesmos campos de controle também estão no formulário de edição do pedido (seção "Controle de produção").

## Exportar para Excel

| Onde | O que gera | Arquivo |
|---|---|---|
| Controle → "Exportar aba 2026" | itens do ano da aba | `controle-de-pedidos-2026.xlsx` |
| Controle → "Exportar todos os anos" | uma aba por ano | `controle-de-pedidos.xlsx` |
| Pedido → "Exportar planilha" | os itens daquele pedido, na aba do ano dele | `pedido-<número>.xlsx` |

O `.xlsx` (`PlanilhaControleExporter`, PhpSpreadsheet) segue o modelo: título "CONTROLE PEDIDOS" em F1 e a data em Q1,
cabeçalho com as mesmas cores e larguras, colunas A e B ocultas, linhas em cinza com bordas, zoom de 85% e sem linhas de grade,
regras de cor reais do Excel (dá para editar a planilha e as cores continuam funcionando) e linhas formatadas até a 250.
Números, datas e quantidades vão como valores do Excel (dá para somar e filtrar). Etapas que são datas viram data; o resto fica como texto.
O cabeçalho fica congelado (melhoria sobre o modelo).

## Como estender

- **Nova coluna de controle**: migration em `pedido_itens`, chave em `PedidoItem::CAMPOS_CONTROLE` (e `ETAPAS`, se for etapa),
  coluna em `ColunasControle::lista()`, `ControleLinhaResource`, tipo `LinhaControle`/`Item` em `types/index.ts`
  e campo em `ItemFormCard.tsx`.
- **Nova cor ou regra**: constantes em `ColunasControle`; a regra do Excel está em `PlanilhaControleExporter::regrasDeCor()`
  e a da tela em `estiloDaLinha()` (`GradeControle.tsx`). Mantenha as duas na mesma ordem de prioridade.
