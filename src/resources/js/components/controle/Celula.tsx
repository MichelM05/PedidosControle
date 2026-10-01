import { useState } from 'react';

import * as f from '@/lib/format';
import { cn } from '@/lib/utils';
import type { ColunaControle, LinhaControle } from '@/types';

/** Colunas que vêm do pedido: ficam só para leitura na grade (edite o pedido na tela dele). */
const SOMENTE_LEITURA = ['pedido', 'cliente', 'numero'];

interface Props {
    linha: LinhaControle;
    coluna: ColunaControle;
    status: Record<string, string>;
    onSalvar: (campo: string, valor: string | null) => void;
}

/** Uma célula da grade. Clique para editar; Enter ou sair do campo salva; Esc cancela. */
export default function Celula({ linha, coluna, status, onSalvar }: Props) {
    const [editando, setEditando] = useState(false);
    const [rascunho, setRascunho] = useState('');
    const bruto = linha[coluna.chave];
    const original = bruto === null || bruto === undefined ? '' : String(bruto);
    const alinha = coluna.alinha === 'left' ? 'text-left' : 'text-center';

    if (SOMENTE_LEITURA.includes(coluna.chave)) return <span className={alinha}>{coluna.chave === 'pedido' ? '' : f.texto(original)}</span>;

    if (coluna.tipo === 'status') {
        return (
            <select
                value={linha.status}
                onChange={(e) => onSalvar('status', e.target.value)}
                aria-label="Status"
                className="w-full cursor-pointer bg-transparent px-1 text-center text-sm font-medium uppercase outline-none focus-visible:ring-2 focus-visible:ring-ring"
            >
                {Object.entries(status).map(([chave, rotulo]) => (
                    <option key={chave} value={chave}>
                        {rotulo}
                    </option>
                ))}
            </select>
        );
    }

    const mostrado = coluna.tipo === 'data' ? (original ? f.data(original) : '') : coluna.tipo === 'numero' && original ? f.quantidade(original) : original;

    const abrir = () => {
        setRascunho(original);
        setEditando(true);
    };
    const confirmar = () => {
        setEditando(false);
        if (rascunho.trim() !== original) onSalvar(coluna.chave, rascunho.trim() === '' ? null : rascunho.trim());
    };

    if (!editando) {
        return (
            <div
                role="button"
                tabIndex={0}
                title="Clique para editar"
                onClick={abrir}
                onKeyDown={(e) => (e.key === 'Enter' || e.key === ' ') && (e.preventDefault(), abrir())}
                className={cn('min-h-6 w-full cursor-text whitespace-pre-wrap px-1 py-0.5', alinha, 'hover:bg-black/5 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none')}
            >
                {mostrado || ' '}
            </div>
        );
    }

    return (
        <input
            autoFocus
            type={coluna.tipo === 'data' ? 'date' : coluna.tipo === 'numero' ? 'number' : 'text'}
            step={coluna.tipo === 'numero' ? 'any' : undefined}
            value={rascunho}
            placeholder={coluna.tipo === 'etapa' ? 'data ou texto' : undefined}
            onChange={(e) => setRascunho(e.target.value)}
            onBlur={confirmar}
            onKeyDown={(e) => {
                if (e.key === 'Enter') e.currentTarget.blur();
                if (e.key === 'Escape') {
                    setRascunho(original);
                    setEditando(false);
                }
            }}
            className={cn('w-full rounded-sm border border-ring bg-white px-1 py-0.5 text-sm outline-none', alinha)}
        />
    );
}
