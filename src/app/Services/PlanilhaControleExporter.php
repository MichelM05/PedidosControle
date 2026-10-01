<?php

namespace App\Services;

use App\Models\PedidoItem;
use App\Support\ColunasControle;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Gera o .xlsx no formato da planilha CONTROLE DE PEDIDOS: mesmas colunas, títulos, larguras, cores
 * e regras de cor (entregue, finalizado e prazo próximo). Uma aba por ano.
 */
class PlanilhaControleExporter
{
    private const PRIMEIRA_LINHA_DADOS = 3;

    /** Como na planilha original, as linhas vazias abaixo dos dados já vêm formatadas (até esta linha, no mínimo). */
    private const LINHA_MINIMA_FORMATADA = 250;

    /**
     * @param  array<int, Collection<int, PedidoItem>>  $itensPorAno  ano => itens (com o pedido carregado)
     */
    public function gerar(array $itensPorAno): Spreadsheet
    {
        $planilha = new Spreadsheet;
        $planilha->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);
        $planilha->removeSheetByIndex(0);

        if ($itensPorAno === []) {
            $itensPorAno = [(int) date('Y') => collect()];
        }

        krsort($itensPorAno);
        foreach ($itensPorAno as $ano => $itens) {
            $aba = $planilha->createSheet();
            $aba->setTitle((string) $ano);
            $this->montarAba($aba, $itens);
        }
        $planilha->setActiveSheetIndex(0);

        return $planilha;
    }

    /** @param Collection<int, PedidoItem> $itens */
    private function montarAba(Worksheet $aba, Collection $itens): void
    {
        $colunas = ColunasControle::lista();
        $ultimaColuna = Coordinate::stringFromColumnIndex(count($colunas)); // Q
        $ultimaLinhaDados = self::PRIMEIRA_LINHA_DADOS + $itens->count() - 1;
        $ultimaLinha = max(self::LINHA_MINIMA_FORMATADA, $ultimaLinhaDados + 50);

        $aba->setShowGridlines(false);
        $aba->getSheetView()->setZoomScale(85)->setZoomScaleNormal(85);
        $aba->getDefaultColumnDimension()->setWidth(14.42578125);
        $aba->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setPaperSize(PageSetup::PAPERSIZE_A4)->setFitToWidth(1)->setFitToHeight(0);
        $aba->getSheetView()->setView('normal');
        $aba->freezePane('A'.self::PRIMEIRA_LINHA_DADOS);

        foreach ($colunas as $i => $coluna) {
            $letra = Coordinate::stringFromColumnIndex($i + 1);
            $dim = $aba->getColumnDimension($letra);
            $dim->setWidth($coluna['largura']);
            $dim->setVisible(! $coluna['oculta']);
        }

        $this->titulo($aba, $ultimaColuna);
        $this->cabecalho($aba, $colunas, $ultimaColuna);

        foreach ($itens->values() as $i => $item) {
            $this->linha($aba, $colunas, self::PRIMEIRA_LINHA_DADOS + $i, $item);
        }

        $this->formatarDados($aba, $ultimaColuna, $ultimaLinha);
        $this->regrasDeCor($aba, $ultimaColuna, $ultimaLinha);
        $aba->setSelectedCell('A'.self::PRIMEIRA_LINHA_DADOS);
    }

    /** Linha 1: título "CONTROLE PEDIDOS" e a data de referência (Q1) usada pela regra de prazo. */
    private function titulo(Worksheet $aba, string $ultimaColuna): void
    {
        $aba->getRowDimension(1)->setRowHeight(18.75);
        $aba->getStyle("A1:{$ultimaColuna}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFFFFF');
        $aba->getStyle("C1:{$ultimaColuna}1")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $aba->setCellValue('F1', 'CONTROLE PEDIDOS');
        $aba->getStyle('F1')->getFont()->setBold(true)->setSize(14);
        $aba->getStyle('F1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $aba->setCellValue("{$ultimaColuna}1", '=TODAY()'); // hoje, sempre atualizado
        $aba->getStyle("{$ultimaColuna}1")->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        $aba->getStyle("{$ultimaColuna}1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private function cabecalho(Worksheet $aba, array $colunas, string $ultimaColuna): void
    {
        $aba->getRowDimension(2)->setRowHeight(45);

        foreach ($colunas as $i => $coluna) {
            $letra = Coordinate::stringFromColumnIndex($i + 1);
            $celula = $aba->getCell("{$letra}2");
            $celula->setValueExplicit($coluna['titulo'], DataType::TYPE_STRING);
            $estilo = $aba->getStyle("{$letra}2");
            $estilo->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF'.$coluna['cor']);
            $estilo->getFont()->setBold(true);
            $estilo->getAlignment()->setHorizontal($coluna['alinha'] === 'left' ? Alignment::HORIZONTAL_LEFT : Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(str_contains($coluna['titulo'], "\n"));
            if ($i >= 2) {
                $b = $estilo->getBorders();
                $b->getTop()->setBorderStyle(Border::BORDER_THIN);
                $b->getLeft()->setBorderStyle(Border::BORDER_THIN);
                $b->getRight()->setBorderStyle(Border::BORDER_THIN);
            }
        }
    }

    private function linha(Worksheet $aba, array $colunas, int $linha, PedidoItem $item): void
    {
        foreach ($colunas as $i => $coluna) {
            $valor = $this->valor($coluna['chave'], $item);
            if ($valor === null || $valor === '') {
                continue;
            }
            $celula = $aba->getCell(Coordinate::stringFromColumnIndex($i + 1).$linha);

            if ($valor instanceof CarbonInterface) {
                $celula->setValue(Date::PHPToExcel($valor));
                $celula->getStyle()->getNumberFormat()->setFormatCode($coluna['chave'] === 'dt_entrega' ? 'dd/mm/yyyy' : 'dd/mm/yy');
            } elseif (is_int($valor) || is_float($valor)) {
                $celula->setValueExplicit($valor, DataType::TYPE_NUMERIC);
            } else {
                $celula->setValueExplicit((string) $valor, DataType::TYPE_STRING);
            }
        }
    }

    /** Valor de uma coluna para um item. Etapas que são datas viram data do Excel; o resto segue como texto. */
    private function valor(string $chave, PedidoItem $item): mixed
    {
        return match ($chave) {
            'pedido' => null, // coluna oculta, sem equivalente no sistema
            'cliente' => $item->pedido?->cliente,
            'numero' => $this->numeroDoPedido($item->pedido?->numero),
            'qtd' => $item->qtd === null ? null : (float) $item->qtd,
            'dt_entrega' => $item->dt_entrega,
            'status' => mb_strtoupper(PedidoItem::STATUS[$item->status] ?? (string) $item->status),
            default => in_array($chave, array_keys(PedidoItem::ETAPAS), true)
                ? $this->etapa($item->$chave)
                : $item->$chave,
        };
    }

    /** O.C do cliente: número quando só tem dígitos (como na planilha), senão texto ("verbal", "726000133 LORAM"). */
    private function numeroDoPedido(?string $numero): int|string|null
    {
        if ($numero === null) {
            return null;
        }

        return ctype_digit($numero) && strlen($numero) <= 15 ? (int) $numero : $numero;
    }

    private function etapa(?string $texto): CarbonInterface|string|null
    {
        $texto = $texto !== null ? trim($texto) : null;
        if ($texto === null || $texto === '') {
            return null;
        }

        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{2}|\d{4})$#', $texto, $m)) {
            $ano = strlen($m[3]) === 2 ? 2000 + (int) $m[3] : (int) $m[3];

            return checkdate((int) $m[2], (int) $m[1], $ano) ? now()->setDate($ano, (int) $m[2], (int) $m[1])->startOfDay() : $texto;
        }
        if (preg_match('#^(\d{4})-(\d{2})-(\d{2})$#', $texto, $m)) {
            return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? now()->setDate((int) $m[1], (int) $m[2], (int) $m[3])->startOfDay() : $texto;
        }

        return $texto;
    }

    private function formatarDados(Worksheet $aba, string $ultimaColuna, int $ultimaLinha): void
    {
        $intervalo = 'A'.self::PRIMEIRA_LINHA_DADOS.":{$ultimaColuna}{$ultimaLinha}";
        $estilo = $aba->getStyle($intervalo);
        $estilo->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF'.ColunasControle::COR_LINHA);
        $estilo->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $aba->getStyle('C'.self::PRIMEIRA_LINHA_DADOS.":{$ultimaColuna}{$ultimaLinha}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $aba->getStyle('D'.self::PRIMEIRA_LINHA_DADOS.":D{$ultimaLinha}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $aba->getStyle('E'.self::PRIMEIRA_LINHA_DADOS.":F{$ultimaLinha}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    }

    /**
     * Regras de cor, em ordem de prioridade: ENTREGUE (azul-acinzentado, riscado), FINALIZADO (verde)
     * e prazo próximo (amarelo: entrega em até 7 dias da data de Q1 e ainda em aberto).
     */
    private function regrasDeCor(Worksheet $aba, string $ultimaColuna, int $ultimaLinha): void
    {
        $primeira = self::PRIMEIRA_LINHA_DADOS;
        $status = $this->letra('status');
        $entrega = $this->letra('dt_entrega');
        $regra = function (string $formula, string $cor, bool $riscado = false): Conditional {
            $c = new Conditional;
            $c->setConditionType(Conditional::CONDITION_EXPRESSION)->addCondition($formula)->setStopIfTrue(true);
            $c->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF'.$cor);
            $c->getStyle()->getFill()->getEndColor()->setARGB('FF'.$cor);
            if ($riscado) {
                $c->getStyle()->getFont()->setStrikethrough(true);
            }

            return $c;
        };

        $aba->getStyle("A{$primeira}:{$ultimaColuna}{$ultimaLinha}")->setConditionalStyles([
            $regra("\${$status}{$primeira}=\"ENTREGUE\"", ColunasControle::COR_ENTREGUE, riscado: true),
            $regra("\${$status}{$primeira}=\"FINALIZADO\"", ColunasControle::COR_FINALIZADO),
            $regra("AND(\${$entrega}{$primeira}<>\"\",(\${$entrega}{$primeira}-".ColunasControle::DIAS_ALERTA.")<=\${$status}\$1)", ColunasControle::COR_PRAZO),
        ]);
    }

    /** Letra da coluna de uma chave de ColunasControle (a data de referência fica na última coluna, a do status). */
    private function letra(string $chave): string
    {
        $indice = array_search($chave, array_column(ColunasControle::lista(), 'chave'), true);

        return Coordinate::stringFromColumnIndex($indice + 1);
    }
}
