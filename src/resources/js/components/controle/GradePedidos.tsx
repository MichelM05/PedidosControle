import { router } from '@inertiajs/react';
import { ChevronDown, ChevronRight, ExternalLink } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { estiloPorSituacao, type Cores, type Prazos } from '@/lib/controle';
import * as f from '@/lib/format';
import { rotas } from '@/lib/routes';
import type { LinhaControle, PedidoControle } from '@/types';

interface Props {
    pedidos: PedidoControle[];
    linhas: LinhaControle[];
    status: Record<string, string>;
    cores: Cores;
    prazos: Prazos;
}

const cabecalho = 'border border-olive/70 bg-muted px-3 py-2 text-left text-xs font-bold uppercase tracking-wide';
const celula = 'border border-olive/40 px-3 py-2 align-middle';

/** Valores distintos e preenchidos, na ordem em que aparecem ("Curitiba, Ponta Grossa"). */
const distintos = (valores: (string | null)[]) => [...new Set(valores.map((v) => v?.trim()).filter(Boolean) as string[])].join(', ') || f.VAZIO;

/**
 * Controle por pedido: uma linha por pedido (situação geral, próxima entrega, cidades e responsáveis) que expande
 * para os itens em forma resumida. Somente leitura: abrir o pedido leva à tela onde o controle é editado.
 */
export default function GradePedidos({ pedidos, linhas, status, cores, prazos }: Props) {
    const [abertos, setAbertos] = useState<Set<number>>(new Set());

    const itensDe = (id: number) => linhas.filter((l) => l.pedido_id === id);
    // Mais urgente primeiro: entrega mais próxima (sem data por último), depois o pedido mais novo
    const ordenados = [...pedidos].sort((a, b) => (a.proxima_entrega ?? '9999').localeCompare(b.proxima_entrega ?? '9999') || b.id - a.id);
    const todosAbertos = ordenados.length > 0 && abertos.size === ordenados.length;

    const alternar = (id: number) =>
        setAbertos((atual) => {
            const novo = new Set(atual);
            if (!novo.delete(id)) novo.add(id);
            return novo;
        });
    const abrir = (id: number) => router.visit(rotas.ver(id));

    return (
        <div className="grid gap-2">
            {ordenados.length > 1 && (
                <div className="flex justify-end">
                    <Button size="sm" variant="outline" onClick={() => setAbertos(todosAbertos ? new Set() : new Set(ordenados.map((p) => p.id)))}>
                        {todosAbertos ? 'Recolher todos' : 'Expandir todos'}
                    </Button>
                </div>
            )}

            <div className="max-h-[70vh] overflow-auto rounded-lg border border-olive bg-white">
                <table className="w-full border-collapse text-sm">
                    <thead className="sticky top-0 z-10">
                        <tr>
                            <th className={`${cabecalho} w-10`} aria-label="Expandir" />
                            <th className={cabecalho}>Pedido</th>
                            <th className={cabecalho}>Cliente</th>
                            <th className={cabecalho}>Itens</th>
                            <th className={cabecalho}>Próxima entrega</th>
                            <th className={cabecalho}>Cidades</th>
                            <th className={cabecalho}>Responsáveis</th>
                            <th className={cabecalho}>Status</th>
                            <th className={`${cabecalho} w-12`} aria-label="Abrir" />
                        </tr>
                    </thead>
                    <tbody>
                        {ordenados.length === 0 && (
                            <tr>
                                <td colSpan={9} className="py-12 text-center text-muted-foreground">
                                    Nenhum pedido neste filtro.
                                </td>
                            </tr>
                        )}
                        {ordenados.map((pedido) => {
                            const itens = itensDe(pedido.id);
                            const aberto = abertos.has(pedido.id);
                            const { background } = estiloPorSituacao(pedido.status_geral, pedido.proxima_entrega, cores, prazos);

                            return (
                                <PedidoLinhas key={pedido.id} aberto={aberto}>
                                    <tr
                                        style={{ background }}
                                        className="cursor-pointer hover:brightness-95"
                                        onClick={() => alternar(pedido.id)}
                                        title={aberto ? 'Recolher itens' : 'Ver itens'}
                                    >
                                        <td className={`${celula} text-center`}>
                                            <button
                                                type="button"
                                                aria-expanded={aberto}
                                                aria-label={aberto ? 'Recolher itens' : 'Expandir itens'}
                                                onClick={(e) => {
                                                    e.stopPropagation();
                                                    alternar(pedido.id);
                                                }}
                                                className="rounded p-0.5 outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                            >
                                                {aberto ? <ChevronDown className="size-4" /> : <ChevronRight className="size-4" />}
                                            </button>
                                        </td>
                                        <td className={`${celula} font-extrabold whitespace-nowrap`}>{pedido.numero ?? `#${pedido.id}`}</td>
                                        <td className={celula}>{f.texto(pedido.cliente)}</td>
                                        <td className={celula}>
                                            <span className="font-semibold">
                                                {itens.length === pedido.itens_count ? `${pedido.itens_count} ${pedido.itens_count === 1 ? 'item' : 'itens'}` : `${itens.length} de ${pedido.itens_count} itens`}
                                            </span>
                                            <span className="ml-2 inline-flex flex-wrap gap-x-3 text-xs text-foreground/70">
                                                {Object.entries(pedido.contagem)
                                                    .filter(([, n]) => n > 0)
                                                    .map(([chave, n]) => (
                                                        <span key={chave}>
                                                            {n} {(status[chave] ?? chave).toLowerCase()}
                                                        </span>
                                                    ))}
                                            </span>
                                        </td>
                                        <td className={`${celula} whitespace-nowrap`}>{pedido.proxima_entrega ? f.data(pedido.proxima_entrega) : f.VAZIO}</td>
                                        <td className={celula}>{distintos(itens.map((i) => i.cidade_entrega))}</td>
                                        <td className={celula}>{distintos(itens.map((i) => i.responsavel))}</td>
                                        <td className={`${celula} font-bold uppercase whitespace-nowrap`}>{status[pedido.status_geral] ?? pedido.status_geral}</td>
                                        <td className={`${celula} text-center`}>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon-sm"
                                                title="Abrir o pedido"
                                                aria-label="Abrir o pedido"
                                                onClick={(e) => {
                                                    e.stopPropagation();
                                                    abrir(pedido.id);
                                                }}
                                            >
                                                <ExternalLink />
                                            </Button>
                                        </td>
                                    </tr>

                                    {aberto && (
                                        <tr>
                                            <td />
                                            <td colSpan={8} className="border border-olive/40 bg-muted/40 p-3">
                                                <table className="w-full border-collapse text-sm">
                                                    <thead>
                                                        <tr className="text-left text-xs uppercase tracking-wide text-muted-foreground">
                                                            <th className="px-2 py-1">Item</th>
                                                            <th className="px-2 py-1">Descrição</th>
                                                            <th className="px-2 py-1 text-right">Qtd.</th>
                                                            <th className="px-2 py-1">Entrega</th>
                                                            <th className="px-2 py-1">Cidade</th>
                                                            <th className="px-2 py-1">Responsável</th>
                                                            <th className="px-2 py-1">Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        {itens.map((item, i) => {
                                                            const { background: cor, riscado } = estiloPorSituacao(item.status, item.dt_entrega, cores, prazos);

                                                            return (
                                                                <tr
                                                                    key={item.id}
                                                                    tabIndex={0}
                                                                    title="Abrir o pedido"
                                                                    onClick={() => abrir(pedido.id)}
                                                                    onKeyDown={(e) => e.key === 'Enter' && abrir(pedido.id)}
                                                                    className="cursor-pointer border-t border-olive/30 bg-white outline-none hover:bg-accent focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-inset"
                                                                >
                                                                    <td className="border-l-[6px] px-2 py-1.5 font-bold whitespace-nowrap" style={{ borderLeftColor: cor }}>
                                                                        {i + 1}
                                                                    </td>
                                                                    <td className={`px-2 py-1.5 ${riscado ? 'line-through' : ''}`}>{f.texto(item.denominacao)}</td>
                                                                    <td className="px-2 py-1.5 text-right whitespace-nowrap">{item.qtd === null ? f.VAZIO : f.quantidade(item.qtd)}</td>
                                                                    <td className="px-2 py-1.5 whitespace-nowrap">{item.dt_entrega ? f.data(item.dt_entrega) : f.VAZIO}</td>
                                                                    <td className="px-2 py-1.5">{f.texto(item.cidade_entrega)}</td>
                                                                    <td className="px-2 py-1.5">{f.texto(item.responsavel)}</td>
                                                                    <td className="px-2 py-1.5">
                                                                        <span className="rounded-full px-2.5 py-0.5 text-xs font-bold uppercase whitespace-nowrap" style={{ background: cor }}>
                                                                            {status[item.status] ?? item.status}
                                                                        </span>
                                                                    </td>
                                                                </tr>
                                                            );
                                                        })}
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                    )}
                                </PedidoLinhas>
                            );
                        })}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

/** Agrupa as linhas (resumo + itens) de um pedido sem criar um elemento a mais na tabela. */
function PedidoLinhas({ children }: { children: React.ReactNode; aberto: boolean }) {
    return <>{children}</>;
}
