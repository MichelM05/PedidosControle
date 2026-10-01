# Parser de PDF

`app/Services/PdfPedidoParser.php` transforma o **texto** do PDF (extraído por smalot/pdfparser) em dados.
Usa expressões regulares sobre o texto linha a linha. O texto completo fica salvo em `pedidos.texto_bruto`
(e aparece na tela em "Ver texto extraído do PDF"), então dá para ajustar o parser sem reenviar o arquivo.

## Modelos de PDF suportados

| | Pedido de Compra | Pedido de Prestação de Serviços |
|---|---|---|
| Número | `Pedido de Compra nº123` | `Ped. Prest. Serv. nº123` |
| Fornecedor | `Dados do Fornecedor` | `Dados do Prestador` |
| Local | `Entrega:` | `Local da Prestação de Serviços:` |

Os demais campos têm o mesmo formato nos dois.

## O que é extraído

**Pedido**: número, data, cliente (nome do bloco *Faturamento*), fornecedor e valor total.

**Cabeçalho** (`dados_extras`): frete, condição de pagamento, comprador, contato e e-mail, moeda,
ICMS/IPI/total dos produtos, observações e quatro blocos de endereço
(fornecedor, faturamento, local, cobrança), cada um com nome, endereço, CNPJ, IE e fone.
As observações são as linhas entre os totais e o texto contratual padrão.

**Itens**: cada item tem uma linha de valores (`1 UR 984.000,00 984.000,00 0,00 % 0,00 %`) e, abaixo,
linhas de metadados (`Dt. Entrega`, `Item Lei`, `Tipo de Manutenção`, `Local da Prestação`,
`Base de Cálculo INSS` e as linhas `==> 0.00 ( Desconto absoluto )`...). O parser associa os
metadados ao item pela ordem. O `Item Lei` pode quebrar em várias linhas e é unido.

A **cidade de entrega** de cada item é o local da prestação sem a UF ("Ponta Grossa PR" → "Ponta Grossa"). Os campos de controle de produção
(etapas, responsável e status) **não** vêm do PDF e nunca são alterados pelo parser.

Valores `==>` e `Base de Cálculo INSS` usam ponto decimal (`1.50`); os demais usam o padrão brasileiro
(`1.234,56`). Os dois casos são tratados.

## Como estender

**Novo campo no cabeçalho** (ex.: "Centro de custo"):
1. Em `extrairDadosExtras()`, adicione a chave com um `$pega('/regex/')`.
2. Se for um campo simples, inclua em `Pedido::CONDICOES` (chave => rótulo) e em `DadosExtras` (`types/index.ts`):
   ele aparece na tela e no modal de edição automaticamente. Para validar, adicione a regra em `AtualizarDadosPedidoRequest`.
3. Escreva um teste em `tests/Unit/PdfPedidoParserTest.php` com um trecho real do PDF.

**Novo campo por item**:
1. Crie a coluna (migration) e inclua em `PedidoItem::CAMPOS_EXTRAS` (já entra em `$fillable`).
2. Reconheça a linha em `extrairMetadadoItem()`.
3. Adicione o campo em `types/index.ts` (interface `Item`), mostre em `components/pedidos/ItemCard.tsx`
   e inclua na lista `SECOES` de `components/pedidos/ItemFormCard.tsx`; adicione a regra em `SavePedidoRequest`.

**Novo modelo de PDF**: ajuste as regexes (ex.: `extrairNumero`) para aceitar o novo texto e adicione um teste.

## Reaplicar o parser em pedidos existentes

```bash
docker compose exec app php artisan pedidos:reextrair            # todos
docker compose exec app php artisan pedidos:reextrair --ids=3 --ids=5
```

Atualiza `dados_extras` e preenche só campos vazios (pedido e itens). Edições manuais são preservadas.

## Limitações

- Testado com os dois modelos acima; outro layout pode exigir novas regexes.
- O nome de uma empresa que quebra em duas linhas no PDF aparece quebrado (o parser não junta).
- Itens e linhas de valores são pareados pela ordem de aparição no texto.
