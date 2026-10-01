import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

import Field from '@/components/Field';
import ListaHistorico from '@/components/historico/ListaHistorico';
import Paginacao from '@/components/Paginacao';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout';
import { rotas } from '@/lib/routes';
import type { GrupoHistorico, Paginador } from '@/types';

interface Props {
    grupos: Paginador<GrupoHistorico>;
    filtros: { q?: string; usuario?: string; acao?: string; pedido?: string; de?: string; ate?: string };
    usuarios: { id: number; name: string }[];
}

const selectClasse = 'h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50';

/** Histórico de alterações de todos os pedidos: quem mudou o quê e quando. */
export default function Index({ grupos, filtros, usuarios }: Props) {
    const [valores, setValores] = useState<Record<string, string>>({
        q: filtros.q ?? '',
        usuario: filtros.usuario ?? '',
        acao: filtros.acao ?? '',
        de: filtros.de ?? '',
        ate: filtros.ate ?? '',
    });
    const pedido = filtros.pedido;

    const aplicar = (e: React.FormEvent) => {
        e.preventDefault();
        const params: Record<string, string> = { ...valores, ...(pedido ? { pedido } : {}) };
        router.get(rotas.historico, Object.fromEntries(Object.entries(params).filter(([, v]) => v)), { preserveState: true });
    };

    return (
        <AppLayout>
            <Head title="Histórico" />

            <Card className="mb-6">
                <CardHeader>
                    <CardTitle className="text-2xl font-extrabold">Histórico de alterações</CardTitle>
                    <p className="text-sm text-muted-foreground">Quem mudou o quê e quando, em todos os pedidos.</p>
                </CardHeader>
                <CardContent>
                    <form onSubmit={aplicar} className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                        <Field id="h-q" label="Buscar" placeholder="Pedido, item ou campo" value={valores.q} onChange={(v) => setValores((a) => ({ ...a, q: v }))} />
                        <div className="grid gap-1.5">
                            <label htmlFor="h-usuario" className="text-xs font-bold uppercase tracking-wide text-muted-foreground">Quem</label>
                            <select id="h-usuario" value={valores.usuario} onChange={(e) => setValores((a) => ({ ...a, usuario: e.target.value }))} className={selectClasse}>
                                <option value="">Todos</option>
                                {usuarios.map((u) => (
                                    <option key={u.id} value={u.id}>
                                        {u.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="grid gap-1.5">
                            <label htmlFor="h-acao" className="text-xs font-bold uppercase tracking-wide text-muted-foreground">O quê</label>
                            <select id="h-acao" value={valores.acao} onChange={(e) => setValores((a) => ({ ...a, acao: e.target.value }))} className={selectClasse}>
                                <option value="">Tudo</option>
                                <option value="criou">Criações</option>
                                <option value="editou">Edições</option>
                                <option value="excluiu">Exclusões</option>
                            </select>
                        </div>
                        <Field id="h-de" label="De (data)" type="date" value={valores.de} onChange={(v) => setValores((a) => ({ ...a, de: v }))} />
                        <Field id="h-ate" label="Até (data)" type="date" value={valores.ate} onChange={(v) => setValores((a) => ({ ...a, ate: v }))} />
                        <div className="flex flex-wrap items-center gap-3 sm:col-span-2 lg:col-span-5">
                            <Button type="submit">Filtrar</Button>
                            <Button type="button" variant="outline" onClick={() => router.get(rotas.historico)}>
                                Limpar filtros
                            </Button>
                            {pedido && <span className="text-sm text-muted-foreground">Filtrando um pedido específico.</span>}
                        </div>
                    </form>
                </CardContent>
            </Card>

            <ListaHistorico grupos={grupos.data} />
            <Paginacao paginador={grupos} />
        </AppLayout>
    );
}
