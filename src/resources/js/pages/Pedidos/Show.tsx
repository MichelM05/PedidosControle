import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';

import EditarSecao, { type Secao } from '@/components/pedidos/EditarSecao';
import ExcluirPedido from '@/components/pedidos/ExcluirPedido';
import ItemCard from '@/components/pedidos/ItemCard';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout';
import * as f from '@/lib/format';
import { rotas } from '@/lib/routes';
import type { BlocoChave, Pedido, Rotulos } from '@/types';

const Rotulo = ({ children }: { children: React.ReactNode }) => (
    <span className="block text-[0.7rem] font-bold uppercase tracking-wide text-muted-foreground">{children}</span>
);

const BotaoEditar = ({ onClick, claro }: { onClick: () => void; claro?: boolean }) => (
    <Button variant="link" size="xs" onClick={onClick} className={claro ? 'text-zinc-300 hover:text-white' : 'text-muted-foreground'}>
        Editar
    </Button>
);

export default function Show({ pedido, rotulos }: { pedido: Pedido; rotulos: Rotulos }) {
    const [secao, setSecao] = useState<Secao | null>(null);
    const extras = pedido.dados_extras ?? {};
    const blocos = extras.blocos ?? {};
    const itens = pedido.itens ?? [];

    const soma = itens.reduce((total, i) => total + Number(i.vlr_tot ?? 0), 0);
    const diferenca = pedido.valor === null ? 0 : Math.abs(soma - Number(pedido.valor));
    const condicoes = Object.entries(rotulos.condicoes).filter(([chave]) => extras[chave as keyof typeof extras]);

    return (
        <AppLayout>
            <Head title={`Pedido nº ${pedido.numero ?? pedido.id}`} />

            <Card>
                <CardContent className="grid gap-5">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <Button asChild variant="outline">
                            <Link href={rotas.index}>← Voltar</Link>
                        </Button>
                        <div className="flex flex-wrap gap-3">
                            {pedido.tem_pdf && (
                                <Button asChild variant="outline">
                                    <a href={rotas.pdf(pedido.id)} target="_blank" rel="noopener">
                                        Abrir PDF original
                                    </a>
                                </Button>
                            )}
                            <Button asChild variant="outline">
                                <Link href={rotas.editar(pedido.id)}>Editar</Link>
                            </Button>
                            <ExcluirPedido pedido={pedido} variant="destructive" />
                        </div>
                    </div>

                    <h2 className="text-2xl font-extrabold">Detalhes do pedido</h2>

                    {/* Resumo */}
                    <div className="flex flex-wrap justify-between gap-4 rounded-xl bg-primary p-6 text-primary-foreground">
                        <div>
                            <span className="block text-[0.7rem] font-bold uppercase tracking-wide opacity-75">Pedido</span>
                            <span className="block text-2xl font-extrabold">nº {f.texto(pedido.numero)}</span>
                            <span className="text-sm opacity-85">{f.data(pedido.data_pedido)}</span>
                        </div>
                        <div className="flex flex-col sm:items-end">
                            <BotaoEditar claro onClick={() => setSecao('resumo')} />
                            <span className="text-[0.7rem] font-bold uppercase tracking-wide opacity-75">Total</span>
                            <span className="text-2xl font-extrabold text-brand-light">{f.moeda(pedido.valor)}</span>
                            <span className="text-sm opacity-85">
                                {itens.length} {itens.length === 1 ? 'item' : 'itens'}
                            </span>
                        </div>
                    </div>

                    {/* Partes: fornecedor, faturamento, local e cobrança */}
                    <div className="grid items-start gap-4 md:grid-cols-2">
                        {Object.keys(blocos).length > 0 ? (
                            (Object.entries(rotulos.blocos) as [BlocoChave, string][]).map(([chave, titulo]) => {
                                const nomeBase = chave === 'fornecedor' ? pedido.fornecedor : chave === 'faturamento' ? pedido.cliente : null;
                                const b = blocos[chave] ?? (nomeBase ? { nome: nomeBase } : undefined);

                                return (
                                    <div key={chave} className="rounded-lg bg-muted px-4 py-3 text-sm">
                                        <div className="flex items-center justify-between">
                                            <Rotulo>{b?.titulo ?? titulo}</Rotulo>
                                            <BotaoEditar onClick={() => setSecao(chave)} />
                                        </div>
                                        {b ? (
                                            <>
                                                <strong className="mb-1 block">{b.nome ?? f.VAZIO}</strong>
                                                {b.endereco?.map((linha, i) => <div key={i}>{linha}</div>)}
                                                {b.cnpj && <div className="text-[0.8125rem] text-muted-foreground">CNPJ: {f.cnpj(b.cnpj)}</div>}
                                                {b.ie && <div className="text-[0.8125rem] text-muted-foreground">IE: {b.ie}</div>}
                                                {b.fone && <div className="text-[0.8125rem] text-muted-foreground">Fone: {b.fone}</div>}
                                            </>
                                        ) : (
                                            <span className="text-[0.8125rem] text-muted-foreground">Sem informações</span>
                                        )}
                                    </div>
                                );
                            })
                        ) : (
                            <>
                                <div className="rounded-lg bg-muted px-4 py-3 text-sm">
                                    <Rotulo>Cliente</Rotulo>
                                    {f.texto(pedido.cliente)}
                                </div>
                                <div className="rounded-lg bg-muted px-4 py-3 text-sm">
                                    <Rotulo>Fornecedor</Rotulo>
                                    {f.texto(pedido.fornecedor)}
                                </div>
                            </>
                        )}
                    </div>

                    {/* Condições comerciais */}
                    <div className="grid gap-x-6 gap-y-3 rounded-xl border p-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
                        <div className="flex items-center justify-between sm:col-span-full">
                            <Rotulo>Condições do pedido</Rotulo>
                            <BotaoEditar onClick={() => setSecao('condicoes')} />
                        </div>
                        {condicoes.map(([chave, rotulo]) => (
                            <div key={chave} className="break-words">
                                <Rotulo>{rotulo}</Rotulo>
                                {chave.startsWith('total_') ? f.moeda(extras[chave as keyof typeof extras] as string) : (extras[chave as keyof typeof extras] as string)}
                            </div>
                        ))}
                        {condicoes.length === 0 && <span className="text-[0.8125rem] text-muted-foreground">Sem informações</span>}
                    </div>

                    {/* Observações */}
                    <div className="rounded-xl bg-muted p-4 text-sm">
                        <div className="flex items-center justify-between">
                            <Rotulo>Observações</Rotulo>
                            <BotaoEditar onClick={() => setSecao('observacoes')} />
                        </div>
                        {extras.observacoes ? (
                            <p className="whitespace-pre-line">{extras.observacoes}</p>
                        ) : (
                            <span className="text-[0.8125rem] text-muted-foreground">Sem observações</span>
                        )}
                    </div>

                    {/* Conferência: só aparece quando o total do PDF difere da soma dos itens */}
                    {diferenca >= 0.01 && (
                        <div className="grid items-center gap-3 rounded-xl border border-l-4 border-l-destructive p-4 text-sm md:grid-cols-3">
                            <div>
                                <Rotulo>Total do pedido (no PDF)</Rotulo>
                                {f.moeda(pedido.valor)}
                            </div>
                            <div>
                                <Rotulo>Soma dos itens</Rotulo>
                                {f.moeda(soma)}
                            </div>
                            <div className="font-bold text-destructive md:text-right">⚠ Diferença de {f.moeda(diferenca)} — confira com o PDF.</div>
                        </div>
                    )}

                    {/* Itens */}
                    <h3 className="font-extrabold text-foreground/80">Itens</h3>
                    {itens.length === 0 ? (
                        <p className="py-12 text-center font-medium text-muted-foreground">Nenhum item neste pedido.</p>
                    ) : (
                        <div className="grid gap-4">
                            {itens.map((item, i) => (
                                <ItemCard key={item.id ?? i} item={item} posicao={i + 1} />
                            ))}
                        </div>
                    )}

                    {pedido.texto_bruto && (
                        <details className="text-sm">
                            <summary className="cursor-pointer font-bold text-muted-foreground">Ver texto extraído do PDF</summary>
                            <pre className="mt-3 max-h-96 overflow-auto rounded-lg border bg-muted p-4 text-xs whitespace-pre-wrap">{pedido.texto_bruto}</pre>
                        </details>
                    )}
                </CardContent>
            </Card>

            {secao && <EditarSecao key={secao} secao={secao} pedido={pedido} rotulos={rotulos} onClose={() => setSecao(null)} />}
        </AppLayout>
    );
}
