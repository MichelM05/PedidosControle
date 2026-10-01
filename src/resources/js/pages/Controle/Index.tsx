import { Head, Link, router } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

import GradeControle from '@/components/controle/GradeControle';
import LegendaCores from '@/components/LegendaCores';
import type { Cores, Prazos } from '@/lib/controle';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout';
import { rotas } from '@/lib/routes';
import { cn } from '@/lib/utils';
import type { ColunaControle, LinhaControle } from '@/types';

interface Props {
    ano: number;
    anos: number[];
    filtros: { q?: string; status?: string; responsavel?: string; ocultar_entregues?: boolean };
    linhas: LinhaControle[];
    totais: Record<string, number>;
    colunas: ColunaControle[];
    status: Record<string, string>;
    responsaveis: string[];
    prazos: Prazos;
    cores: Cores;
}

const selectClasse = 'h-9 rounded-md border bg-background px-3 text-sm shadow-xs outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50';

export default function Index({ ano, anos, filtros, linhas, totais, colunas, status, responsaveis, prazos, cores }: Props) {
    const [busca, setBusca] = useState(filtros.q ?? '');
    const [mostrarOcultas, setMostrarOcultas] = useState(false);
    const primeira = useRef(true);

    const filtrar = (novos: Record<string, unknown>) => {
        const params: Record<string, unknown> = { ano, q: busca, status: filtros.status, responsavel: filtros.responsavel, ocultar_entregues: filtros.ocultar_entregues ? 1 : undefined, ...novos };
        router.get(rotas.controle, Object.fromEntries(Object.entries(params).filter(([, v]) => v !== undefined && v !== '' && v !== false)) as Record<string, string | number>, { preserveState: true, preserveScroll: true, replace: true });
    };

    // Busca com atraso, para não consultar a cada letra digitada
    useEffect(() => {
        if (primeira.current) {
            primeira.current = false;
            return;
        }
        const t = setTimeout(() => filtrar({ q: busca }), 350);
        return () => clearTimeout(t);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [busca]);

    return (
        <AppLayout largura="max-w-none">
            <Head title="Controle de pedidos" />

            <div className="mb-4 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 className="text-2xl font-extrabold">Controle de pedidos</h1>
                    <p className="text-sm text-muted-foreground">Um item por linha, como na planilha. Clique em uma linha para abrir o pedido e editar o controle.</p>
                </div>
                <div className="flex flex-wrap gap-2">
                    <Button asChild variant="outline">
                        <a href={rotas.controleExportar(ano)}>
                            <Download /> Exportar aba {ano}
                        </a>
                    </Button>
                    <Button asChild variant="outline">
                        <a href={rotas.controleExportar()}>
                            <Download /> Exportar todos os anos
                        </a>
                    </Button>
                </div>
            </div>

            {/* Abas por ano, como na planilha */}
            <div className="mb-3 flex flex-wrap gap-1 border-b" role="tablist">
                {anos.map((a) => (
                    <Link
                        key={a}
                        href={rotas.controle}
                        data={{ ano: a }}
                        role="tab"
                        aria-selected={a === ano}
                        className={cn('rounded-t-md border border-b-0 px-4 py-1.5 text-sm font-semibold', a === ano ? 'bg-card text-foreground' : 'bg-muted text-muted-foreground hover:bg-accent')}
                    >
                        {a}
                    </Link>
                ))}
            </div>

            <div className="mb-3 flex flex-wrap items-center gap-3">
                <input
                    type="search"
                    value={busca}
                    onChange={(e) => setBusca(e.target.value)}
                    placeholder="Buscar pedido, cliente, produto ou cidade"
                    aria-label="Buscar"
                    className={cn(selectClasse, 'w-80')}
                />
                <select aria-label="Status" value={filtros.status ?? ''} onChange={(e) => filtrar({ status: e.target.value })} className={selectClasse}>
                    <option value="">Todos os status ({totais.todos})</option>
                    {Object.entries(status).map(([chave, rotulo]) => (
                        <option key={chave} value={chave}>
                            {rotulo} ({totais[chave] ?? 0})
                        </option>
                    ))}
                </select>
                <select aria-label="Responsável" value={filtros.responsavel ?? ''} onChange={(e) => filtrar({ responsavel: e.target.value })} className={selectClasse}>
                    <option value="">Todos os responsáveis</option>
                    {responsaveis.map((r) => (
                        <option key={r} value={r}>
                            {r}
                        </option>
                    ))}
                </select>
                <label className="flex items-center gap-2 text-sm">
                    <input type="checkbox" checked={!!filtros.ocultar_entregues} onChange={(e) => filtrar({ ocultar_entregues: e.target.checked ? 1 : undefined })} />
                    Ocultar entregues e cancelados
                </label>
                <label className="flex items-center gap-2 text-sm">
                    <input type="checkbox" checked={mostrarOcultas} onChange={(e) => setMostrarOcultas(e.target.checked)} />
                    Mostrar colunas ocultas (Pedido, Cliente)
                </label>
            </div>

            <GradeControle linhas={linhas} colunas={colunas} status={status} cores={cores} prazos={prazos} mostrarOcultas={mostrarOcultas} />

            <LegendaCores cores={cores} prazos={prazos} />
        </AppLayout>
    );
}
