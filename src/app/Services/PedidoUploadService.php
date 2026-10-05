<?php

namespace App\Services;

use App\Models\Pedido;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Smalot\PdfParser\Parser;
use Throwable;

class PedidoUploadService
{
    /** Limite de texto extraído do PDF (protege o servidor de arquivos gigantes). */
    private const MAX_CARACTERES = 3_000_000;

    public function __construct(private PdfPedidoParser $parser, private HistoricoService $historico) {}

    /**
     * Lê o PDF, guarda o arquivo original e cria o pedido com seus itens.
     *
     * @throws Throwable
     */
    public function processarUpload(UploadedFile $arquivo): Pedido
    {
        $texto = (new Parser)->parseFile($arquivo->getRealPath())->getText();
        if (mb_strlen($texto) > self::MAX_CARACTERES) {
            throw new InvalidArgumentException('O PDF tem texto demais para ser processado (limite de '.number_format(self::MAX_CARACTERES, 0, ',', '.').' caracteres).');
        }

        $extraido = $this->parser->extrair($texto);
        $caminho = $arquivo->store('pdfs');

        try {
            return DB::transaction(function () use ($extraido, $texto, $caminho) {
                $pedido = Pedido::create($extraido['pedido'] + [
                    'texto_bruto' => $texto,
                    'arquivo_pdf' => $caminho,
                    'dados_extras' => $extraido['dados_extras'],
                ]);
                $this->historico->semRegistrarItens(fn () => $pedido->itens()->createMany($extraido['itens']));
                $this->historico->pedidoCriado($pedido, 'Importado do PDF');

                return $pedido;
            });
        } catch (Throwable $e) {
            Storage::delete($caminho); // não deixa PDF órfão se o banco falhar
            throw $e;
        }
    }
}
