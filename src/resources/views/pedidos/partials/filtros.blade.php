@php
    $filtros = $filtros ?? [];
@endphp

<div class="card">
    <h2>Filtros</h2>

    <form action="{{ route('pedidos.index') }}" method="GET" class="filters-form">
        <div class="filters-field">
            <label for="numero">Número</label>
            <input type="text" name="numero" id="numero" class="form-control" value="{{ e($filtros['numero'] ?? '') }}">
        </div>

        <div class="filters-field">
            <label for="cliente">Cliente</label>
            <input type="text" name="cliente" id="cliente" class="form-control" value="{{ e($filtros['cliente'] ?? '') }}">
        </div>

        <div class="filters-field">
            <label for="fornecedor">Fornecedor</label>
            <input type="text" name="fornecedor" id="fornecedor" class="form-control" value="{{ e($filtros['fornecedor'] ?? '') }}">
        </div>

        <div class="filters-field">
            <label for="data_inicio">Data inicial</label>
            <input type="date" name="data_inicio" id="data_inicio" class="form-control" value="{{ e($filtros['data_inicio'] ?? '') }}">
        </div>

        <div class="filters-field">
            <label for="data_fim">Data final</label>
            <input type="date" name="data_fim" id="data_fim" class="form-control" value="{{ e($filtros['data_fim'] ?? '') }}">
        </div>

        <div class="filters-field">
            <label for="valor_min">Valor mín. (R$)</label>
            <input type="text" name="valor_min" id="valor_min" class="form-control" value="{{ e($filtros['valor_min'] ?? '') }}" placeholder="Ex: 1000,00">
        </div>

        <div class="filters-field">
            <label for="valor_max">Valor máx. (R$)</label>
            <input type="text" name="valor_max" id="valor_max" class="form-control" value="{{ e($filtros['valor_max'] ?? '') }}" placeholder="Ex: 5000,00">
        </div>

        <div class="filters-actions">
            <button type="submit" class="btn btn-primary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                Filtrar resultados
            </button>
            <a href="{{ route('pedidos.index') }}" class="btn btn-secondary">Limpar filtros</a>
        </div>
    </form>
</div>
