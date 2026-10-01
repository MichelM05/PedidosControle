import { Head, Link, usePage } from '@inertiajs/react';

import Paginacao from '@/components/Paginacao';
import ExcluirPedido from '@/components/pedidos/ExcluirPedido';
import Filtros from '@/components/pedidos/Filtros';
import UploadCard from '@/components/pedidos/UploadCard';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout';
import * as f from '@/lib/format';
import { rotas } from '@/lib/routes';
import type { PageProps, Paginador, Pedido } from '@/types';

interface Props {
    pedidos: Paginador<Pedido>;
    filtros: Record<string, string>;
}

export default function Index({ pedidos, filtros }: Props) {
    const { errors } = usePage<PageProps>().props;
    const filtrado = Object.values(filtros).some(Boolean);

    return (
        <AppLayout>
            <Head title="Pedidos" />

            <UploadCard />
            <Filtros filtros={filtros} erros={errors} />

            <Card>
                <CardHeader>
                    <CardTitle className="text-2xl font-extrabold">Pedidos importados</CardTitle>
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
                                            <TableHead>Nº pedido</TableHead>
                                            <TableHead>Data</TableHead>
                                            <TableHead>Cliente</TableHead>
                                            <TableHead>Fornecedor</TableHead>
                                            <TableHead className="text-right">Itens</TableHead>
                                            <TableHead className="text-right">Total</TableHead>
                                            <TableHead className="text-right">Ações</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {pedidos.data.map((p) => (
                                            <TableRow key={p.id}>
                                                <TableCell>{f.texto(p.numero)}</TableCell>
                                                <TableCell>{f.data(p.data_pedido)}</TableCell>
                                                <TableCell className="max-w-48 whitespace-normal">{f.texto(p.cliente)}</TableCell>
                                                <TableCell className="max-w-48 whitespace-normal">{f.texto(p.fornecedor)}</TableCell>
                                                <TableCell className="text-right">{p.itens_count}</TableCell>
                                                <TableCell className="text-right whitespace-nowrap">{f.moeda(p.valor)}</TableCell>
                                                <TableCell>
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
                        </>
                    )}
                </CardContent>
            </Card>
        </AppLayout>
    );
}
