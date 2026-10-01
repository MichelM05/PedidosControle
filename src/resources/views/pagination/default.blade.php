@if ($paginator->hasPages())
    <nav class="pagination" role="navigation" aria-label="Paginação">
        <span class="pagination-info">
            Mostrando {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} de {{ $paginator->total() }}
        </span>

        <div class="pagination-links">
            @if ($paginator->onFirstPage())
                <span class="btn btn-actions is-disabled">&larr; Anterior</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="btn btn-actions" rel="prev">&larr; Anterior</a>
            @endif

            <span class="pagination-pagina">Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}</span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="btn btn-actions" rel="next">Próxima &rarr;</a>
            @else
                <span class="btn btn-actions is-disabled">Próxima &rarr;</span>
            @endif
        </div>
    </nav>
@endif
