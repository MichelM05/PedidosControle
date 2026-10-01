<div class="upload-card-container">
    <div class="upload-card-icon">
        <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 24 24">
            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zM13 9V3.5L18.5 9H13z"></path>
        </svg>
    </div>

    <h2 class="upload-card-title">Importar pedido em PDF</h2>
    <p class="upload-card-description">A importação extrai do PDF: número do pedido, data, cliente, fornecedor,
        valor total e itens detalhados (material, denominação, quantidade, unidade, preço, valor total, impostos,
        data de entrega, item de lei, tipo de manutenção e local da prestação).</p>

    <form action="{{ route('pedidos.upload') }}" method="POST" enctype="multipart/form-data" class="upload-form">
        @csrf

        <div class="file-upload-wrapper mb-6">
            <label for="pdf" id="file-label" class="upload-label" data-original-text="Selecione o PDF">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span id="file-label-text">Selecione o PDF</span>
            </label>
            <input type="file" name="pdf" id="pdf" accept=".pdf" required>
            <div class="upload-info">
                <span id="file-name" class="upload-filename" data-original-text="Nenhum arquivo selecionado">Nenhum arquivo selecionado</span>
                <span class="upload-hint">No momento aceitamos apenas arquivos <strong>.PDF</strong></span>
            </div>
            <button type="button" id="btn-remove" class="btn-remove" style="display: none;">&times;</button>
        </div>

        <div class="flex gap-4 items-center">
            <button type="submit" id="btn-submit" class="upload-submit-btn" data-loading-text="Processando...">
                Processar PDF
            </button>

            <a href="{{ route('pedidos.create') }}" class="upload-submit-btn upload-submit-btn-outline">
                Criar pedido manual
            </a>
        </div>

    </form>
</div>
