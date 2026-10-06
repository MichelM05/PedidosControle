<?php

namespace App\Services;

use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Support\ColunasControle;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Exportação resumida do controle: uma linha por pedido (sem os itens), uma aba por ano.
 * A cor da linha segue a situação geral e o prazo da entrega mais próxima, como na tela.
 */
class PlanilhaPedidosExporter
{
    /** chave => [título, largura, alinhamento] */
    private const COLUNAS = [
        'numero' => ['O.C CLIENTE', 18, 'left'],
        'cliente' => ['CLIENTE', 30, 'left'],
        'data_pedido' => ['DATA DO PEDIDO', 14, 'center'],
        'itens' => ['ITENS', 9, 'center'],
        'andamento' => ['EM ANDAMENTO', 13, 'center'],
        'finalizado' => ['FINALIZADOS', 13, 'center'],
        'entregue' => ['ENTREGUES', 11, 'center'],
        'cancelado' => ['CANCELADOS', 12, 'center'],
        'valor' => ['VALOR TOTAL', 16, 'right'],
        'proxima_entrega' => ['PRÓXIMA ENTREGA', 16, 'center'],
        'cidades' => ['CIDADES ENTREGA', 28, 'left'],
        'responsaveis' => ['RESPONSÁVEIS', 24, 'left'],
        'status' => ['STATUS', 14, 'center'],
    ];

    /** @param  array<int, Collection<int, Pedido>>  $pedidosPorAno  ano => pedidos (com os itens carregados) */
    public function gerar(array $pedidosPorAno): Spreadsheet
    {
        $planilha = new Spreadsheet;
        $planilha->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);
        $planilha->removeSheetByIndex(0);

        if ($pedidosPorAno === []) {
            $pedidosPorAno = [(int) date('Y') => collect()];
        }

        krsort($pedidosPorAno);
        foreach ($pedidosPorAno as $ano => $pedidos) {
            $aba = $planilha->createSheet();
            $aba->setTitle((string) $ano);
            $this->montarAba($aba, $pedidos, (int) $ano);
        }
        $planilha->setActiveSheetIndex(0);

        return $planilha;
    }

    private const COR_TITULO = '3A3A2C';

    private const COR_BORDA = 'BFBFB5';

    /** @param Collection<int, Pedido> $pedidos */
    private function montarAba(Worksheet $aba, Collection $pedidos, int $ano): void
    {
        $ultima = Coordinate::stringFromColumnIndex(count(self::COLUNAS));
        $aba->setShowGridlines(false);
        $aba->getSheetView()->setZoomScale(90)->setZoomScaleNormal(90);
        $aba->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setPaperSize(PageSetup::PAPERSIZE_A4)->setFitToWidth(1)->setFitToHeight(0);
        $aba->getPageSetup()->setFitToPage(true);
        $aba->freezePane('A4');

        // Título e subtítulo
        $aba->mergeCells("A1:{$ultima}1");
        $aba->setCellValue('A1', "RESUMO DE PEDIDOS — $ano");
        $aba->getRowDimension(1)->setRowHeight(30);
        $aba->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setARGB('FF'.self::COR_TITULO);
        $aba->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER)->setIndent(1);
        $aba->mergeCells("A2:{$ultima}2");
        $aba->setCellValue('A2', 'Gerado em '.now()->format('d/m/Y').' · uma linha por pedido · cor pela situação e prazo da próxima entrega');
        $aba->getStyle('A2')->getFont()->setItalic(true)->setSize(10)->getColor()->setARGB('FF6B6B5B');
        $aba->getStyle('A2')->getAlignment()->setIndent(1);

        // Cabeçalho escuro com texto branco
        $i = 0;
        foreach (self::COLUNAS as [$titulo, $largura]) {
            $letra = Coordinate::stringFromColumnIndex(++$i);
            $aba->getColumnDimension($letra)->setWidth($largura);
            $aba->getCell("{$letra}3")->setValueExplicit($titulo, DataType::TYPE_STRING);
        }
        $aba->getRowDimension(3)->setRowHeight(30);
        $cabecalho = $aba->getStyle("A3:{$ultima}3");
        $cabecalho->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF'.self::COR_TITULO);
        $cabecalho->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $cabecalho->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);

        $linha = 4;
        foreach ($this->ordenar($pedidos) as $pedido) {
            $this->linha($aba, $linha++, $pedido, $ultima);
        }
        $ultimaLinha = $linha - 1;

        if ($ultimaLinha >= 4) {
            $corpo = $aba->getStyle("A4:{$ultima}{$ultimaLinha}");
            $corpo->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FF'.self::COR_BORDA);
            $corpo->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $this->totais($aba, $ultimaLinha + 1, $ultimaLinha, $ultima);
        }
        $aba->setAutoFilter("A3:{$ultima}".max(4, $ultimaLinha));
        $aba->setSelectedCell('A4');
    }

    /** Linha de totais (fórmulas, então acompanham o filtro e as edições): itens por status e valor. */
    private function totais(Worksheet $aba, int $linha, int $ultimaLinha, string $ultima): void
    {
        $aba->setCellValue("A{$linha}", 'TOTAL');
        $i = 0;
        foreach (array_keys(self::COLUNAS) as $chave) {
            $letra = Coordinate::stringFromColumnIndex(++$i);
            if (in_array($chave, ['itens', 'andamento', 'finalizado', 'entregue', 'cancelado', 'valor'], true)) {
                $aba->setCellValue("{$letra}{$linha}", "=SUBTOTAL(109,{$letra}4:{$letra}{$ultimaLinha})");
            }
        }
        $aba->getStyle("D{$linha}:H{$linha}")->getNumberFormat()->setFormatCode('0');
        $aba->getStyle("I{$linha}")->getNumberFormat()->setFormatCode('R$ #,##0.00');
        $aba->getRowDimension($linha)->setRowHeight(24);
        $estilo = $aba->getStyle("A{$linha}:{$ultima}{$linha}");
        $estilo->getFont()->setBold(true);
        $estilo->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE7E6E6');
        $estilo->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM);
        $estilo->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $aba->getStyle("D{$linha}:I{$linha}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $aba->getStyle("I{$linha}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }

    /** Entrega mais próxima primeiro (sem data por último), como na tela. */
    private function ordenar(Collection $pedidos): Collection
    {
        return $pedidos->sortBy(fn (Pedido $p) => [$this->proximaEntrega($p) ?? '9999-12-31', -$p->id])->values();
    }

    /** Entrega mais próxima entre os itens em andamento (Y-m-d). */
    private function proximaEntrega(Pedido $pedido): ?string
    {
        return $pedido->itens->where('status', 'andamento')->pluck('dt_entrega')->filter()->map->format('Y-m-d')->min();
    }

    private function linha(Worksheet $aba, int $linha, Pedido $pedido, string $ultima): void
    {
        $itens = $pedido->itens;
        $conta = fn (string $status) => $itens->where('status', $status)->count();
        $situacao = Pedido::situacaoGeral($itens->count(), $conta('cancelado'), $conta('entregue'), $conta('andamento'));
        $proxima = $this->proximaEntrega($pedido);
        $distintos = fn (string $campo) => $itens->pluck($campo)->map(fn ($v) => trim((string) $v))->filter()->unique()->implode(', ');

        $valores = [
            'numero' => ctype_digit((string) $pedido->numero) && strlen((string) $pedido->numero) <= 15 ? (int) $pedido->numero : $pedido->numero,
            'cliente' => $pedido->cliente,
            'data_pedido' => $pedido->data_pedido,
            'itens' => $itens->count(),
            'andamento' => $conta('andamento'),
            'finalizado' => $conta('finalizado'),
            'entregue' => $conta('entregue'),
            'cancelado' => $conta('cancelado'),
            'valor' => $pedido->valor === null ? null : (float) $pedido->valor,
            'proxima_entrega' => $proxima,
            'cidades' => $distintos('cidade_entrega'),
            'responsaveis' => $distintos('responsavel'),
            'status' => mb_strtoupper(PedidoItem::STATUS[$situacao] ?? $situacao),
        ];

        $aba->getRowDimension($linha)->setRowHeight(22);
        $i = 0;
        foreach (self::COLUNAS as $chave => [, , $alinha]) {
            $letra = Coordinate::stringFromColumnIndex(++$i);
            $valor = $valores[$chave];
            $celula = $aba->getCell("{$letra}{$linha}");
            $aba->getStyle("{$letra}{$linha}")->getAlignment()->setHorizontal($alinha)->setWrapText(in_array($chave, ['cliente', 'cidades', 'responsaveis'], true));
            if ($alinha === 'left') {
                $aba->getStyle("{$letra}{$linha}")->getAlignment()->setIndent(1);
            }

            if ($valor === null || $valor === '') {
                continue;
            }
            if ($chave === 'status') {
                $aba->getStyle("{$letra}{$linha}")->getFont()->setBold(true);
            }
            if ($chave === 'data_pedido' || $chave === 'proxima_entrega') {
                $celula->setValue(Date::PHPToExcel(is_string($valor) ? Carbon::parse($valor) : $valor));
                $celula->getStyle()->getNumberFormat()->setFormatCode('dd/mm/yyyy');
            } elseif ($chave === 'valor') {
                $celula->setValueExplicit($valor, DataType::TYPE_NUMERIC);
                $celula->getStyle()->getNumberFormat()->setFormatCode('R$ #,##0.00');
            } elseif (is_int($valor)) {
                $celula->setValueExplicit($valor, DataType::TYPE_NUMERIC);
                if (! in_array($chave, ['numero', 'itens'], true)) {
                    $celula->getStyle()->getNumberFormat()->setFormatCode('0;-0;"–"'); // contagem zerada vira traço
                }
            } else {
                $celula->setValueExplicit((string) $valor, DataType::TYPE_STRING);
            }
        }

        $aba->getStyle("A{$linha}:{$ultima}{$linha}")->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF'.ColunasControle::corDaSituacao($situacao, $proxima));
    }
}
