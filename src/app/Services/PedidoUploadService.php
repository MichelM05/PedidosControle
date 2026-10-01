<?php

namespace App\Services;

use App\Models\Pedido;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use Throwable;

class PedidoUploadService
{
    public function __construct(private PdfPedidoParser $parser) {}

    /**
     * Lê o PDF, guarda o arquivo original e cria o pedido com seus itens.
     *
     * @throws Throwable
     */
    public function processarUpload(UploadedFile $arquivo): Pedido
    {
        $texto = (new Parser)->parseFile($arquivo->getRealPath())->getText();
        $extraido = $this->parser->extrair($texto);
        $caminho = $arquivo->store('pdfs');

        try {
            return DB::transaction(function () use ($extraido, $texto, $caminho) {
                $pedido = Pedido::create($extraido['pedido'] + [
                    'texto_bruto' => $texto,
                    'arquivo_pdf' => $caminho,
                    'dados_extras' => $extraido['dados_extras'],
                ]);
                $pedido->itens()->createMany($extraido['itens']);

                return $pedido;
            });
        } catch (Throwable $e) {
            Storage::delete($caminho); // não deixa PDF órfão se o banco falhar
            throw $e;
        }
    }
}
