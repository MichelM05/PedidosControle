import { Link } from '@inertiajs/react';

import Celula from '@/components/controle/Celula';
import { rotas } from '@/lib/routes';
import type { ColunaControle, LinhaControle } from '@/types';

interface Cores {
    linha: string;
    entregue: string;
    finalizado: string;
    prazo: string;
}

interface Props {
    linhas: LinhaControle[];
    colunas: ColunaControle[];
    status: Record<string, string>;
    cores: Cores;
    diasAlerta: number;
    mostrarOcultas: boolean;
    onSalvar: (linha: LinhaControle, campo: string, valor: string | null) => void;
}

/** Largura em pixels a partir da largura de coluna do Excel. */
const px = (largura: number) => Math.round(largura * 7.6 + 10);

/** Cor e estilo da linha, na mesma ordem de prioridade da planilha exportada: entregue, finalizado, prazo próximo. */
function estiloDaLinha(linha: LinhaControle, cores: Cores, diasAlerta: number): { background: string; riscado: boolean } {
    if (linha.status === 'entregue') return { background: `#${cores.entregue}`, riscado: true };
    if (linha.status === 'finalizado') return { background: `#${cores.finalizado}`, riscado: false };

    if (linha.dt_entrega) {
        const limite = new Date();
        limite.setHours(0, 0, 0, 0);
        limite.setDate(limite.getDate() + diasAlerta);
        // "AAAA-MM-DD" como data local (new Date('AAAA-MM-DD') seria UTC e erraria o dia)
        const [a, m, d] = linha.dt_entrega.split('-').map(Number);
        if (new Date(a, m - 1, d) <= limite) return { background: `#${cores.prazo}`, riscado: false };
    }
    return { background: `#${cores.linha}`, riscado: false };
}

/** Grade no formato da planilha CONTROLE DE PEDIDOS: mesmas colunas, grupos de cor e regras de cor por linha. */
export default function GradeControle({ linhas, colunas, status, cores, diasAlerta, mostrarOcultas, onSalvar }: Props) {
    const visiveis = colunas.filter((c) => mostrarOcultas || !c.oculta);

    return (
        <div className="max-h-[70vh] overflow-auto rounded-lg border border-zinc-400 bg-white">
            <table className="border-collapse text-sm" style={{ tableLayout: 'fixed', width: visiveis.reduce((total, c) => total + px(c.largura), 0) }}>
                <colgroup>
                    {visiveis.map((c) => (
                        <col key={c.chave} style={{ width: px(c.largura) }} />
                    ))}
                </colgroup>
                <thead className="sticky top-0 z-10">
                    <tr className="h-14">
                        {visiveis.map((c) => (
                            <th
                                key={c.chave}
                                style={{ background: `#${c.cor}` }}
                                className={`border border-zinc-500 px-1 text-xs font-bold whitespace-pre-line ${c.alinha === 'left' ? 'text-left' : 'text-center'}`}
                            >
                                {c.titulo.replace(/\s*\n\s*/g, ' ')}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {linhas.length === 0 && (
                        <tr>
                            <td colSpan={visiveis.length} className="py-12 text-center text-muted-foreground">
                                Nenhum item neste filtro.
                            </td>
                        </tr>
                    )}
                    {linhas.map((linha) => {
                        const { background, riscado } = estiloDaLinha(linha, cores, diasAlerta);

                        return (
                            <tr key={linha.id} style={{ background }} className={riscado ? 'line-through' : undefined}>
                                {visiveis.map((c) => (
                                    <td key={c.chave} className="border border-zinc-500 p-0 align-middle">
                                        {c.chave === 'numero' ? (
                                            <Link href={rotas.ver(linha.pedido_id)} className="block px-1 py-0.5 text-center underline-offset-2 hover:underline" title="Abrir o pedido">
                                                {linha.numero ?? '—'}
                                            </Link>
                                        ) : (
                                            <Celula linha={linha} coluna={c} status={status} onSalvar={(campo, valor) => onSalvar(linha, campo, valor)} />
                                        )}
                                    </td>
                                ))}
                            </tr>
                        );
                    })}
                </tbody>
            </table>
        </div>
    );
}
