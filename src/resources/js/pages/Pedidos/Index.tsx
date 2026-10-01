import { Head, Link, usePage } from '@inertiajs/react';

import LegendaCores from '@/components/LegendaCores';
import Paginacao from '@/components/Paginacao';
import ExcluirPedido from '@/components/pedidos/ExcluirPedido';
import Filtros from '@/components/pedidos/Filtros';
import UploadCard from '@/components/pedidos/UploadCard';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout';
import * as f from '@/lib/format';
import { estiloPorSituacao, type Cores, type Prazos } from '@/lib/controle';
import { rotas } from '@/lib/routes';
import type { PageProps, Paginador, Pedido } from '@/types';

interface Props {
    pedidos: Paginador<Pedido>;
    filtros: Record<string, string>;
    status: Record<string, string>;
    cores: Cores;
    prazos: Prazos;
}

export default function Index({ pedidos, filtros, status, cores, prazos }: Props) {
    const { errors } = usePage<PageProps>().props;
    const filtrado = Object.values(filtros).some(Boolean);

    return (
        <AppLayout>
            <Head title="Pedidos" />

            <UploadCard />
            <Filtros filtros={filtros} erros={errors} />

            <Card>
                <CardHeader>
                    <CardTitle className="text-2xl font-extrabold">Pedidos cadastrados</CardTitle>
                </CardHeader>
                <CardContent>
                    {pedidos.data.length === 0 ? (
                        <p className="py-12 text-center font-medium text-muted-foreground">
                            {filtrado ? 'Nenhum pedido encontrado com esses filtros.' : 'Nenhum pedido ainda. Envie um PDF acima.'}
                        </p>
                    ) : (
                        <>
                            <div className="rounded-xl border">
                                <Table>
                                    <TableHeader>
                                        <TableRow className="bg-muted/60 text-xs uppercase">
                                            <TableHead className="px-4 py-3">Nº pedido</TableHead>
                                            <TableHead className="px-4 py-3">Data de entrega</TableHead>
                                            <TableHead className="px-4 py-3">Cliente</TableHead>
                                            <TableHead className="px-4 py-3">Fornecedor</TableHead>
                                            <TableHead className="px-4 py-3 text-right">Itens</TableHead>
                                            <TableHead className="px-4 py-3 text-right">Total</TableHead>
                                            <TableHead className="px-4 py-3">Situação</TableHead>
                                            <TableHead className="px-4 py-3 text-right">Ações</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {pedidos.data.map((p) => (
                                            <TableRow
                                                key={p.id}
                                                style={{ background: estiloPorSituacao(p.status_geral, p.proxima_entrega, cores, prazos).background }}
                                                className="hover:brightness-95"
                                            >
                                                <TableCell className="px-4 py-3">{f.texto(p.numero)}</TableCell>
                                                <TableCell className="px-4 py-3 whitespace-nowrap">{f.data(p.data_entrega)}</TableCell>
                                                <TableCell className="max-w-56 px-4 py-3 whitespace-normal">{f.texto(p.cliente)}</TableCell>
                                                <TableCell className="max-w-56 px-4 py-3 whitespace-normal">{f.texto(p.fornecedor)}</TableCell>
                                                <TableCell className="px-4 py-3 text-right">{p.itens_count}</TableCell>
                                                <TableCell className="px-4 py-3 text-right whitespace-nowrap">{f.moeda(p.valor)}</TableCell>
                                                <TableCell className="px-4 py-3 text-xs font-semibold uppercase whitespace-nowrap">{status[p.status_geral ?? 'andamento']}</TableCell>
                                                <TableCell className="px-4 py-3">
                                                    <div className="flex justify-end gap-2">
                                                        <Button asChild variant="outline" size="sm">
                                                            <Link href={rotas.ver(p.id)}>Ver detalhes</Link>
                                                        </Button>
                                                        <Button asChild variant="outline" size="sm">
                                                            <Link href={rotas.editar(p.id)}>Editar</Link>
                                                        </Button>
                                                        <ExcluirPedido pedido={p} />
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                            <Paginacao paginador={pedidos} />
                            <LegendaCores cores={cores} prazos={prazos} />
                        </>
                    )}
                </CardContent>
            </Card>
        </AppLayout>
    );
}
