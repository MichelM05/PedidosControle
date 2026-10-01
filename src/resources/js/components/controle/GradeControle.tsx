import { router } from '@inertiajs/react';

import { rotas } from '@/lib/routes';
import * as f from '@/lib/format';
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

/** Texto de uma célula, formatado como na planilha. */
function texto(linha: LinhaControle, coluna: ColunaControle, status: Record<string, string>): string {
    const valor = linha[coluna.chave];
    if (valor === null || valor === undefined || valor === '' || coluna.chave === 'pedido') return '';
    if (coluna.tipo === 'data') return f.data(String(valor));
    if (coluna.tipo === 'numero') return f.quantidade(valor);
    if (coluna.tipo === 'status') return (status[String(valor)] ?? String(valor)).toUpperCase();
    return String(valor);
}

/**
 * Grade no formato da planilha CONTROLE DE PEDIDOS: mesmas colunas, grupos de cor e regras de cor por linha.
 * Somente leitura: clicar em uma linha abre o pedido, onde o controle é editado.
 */
export default function GradeControle({ linhas, colunas, status, cores, diasAlerta, mostrarOcultas }: Props) {
    const visiveis = colunas.filter((c) => mostrarOcultas || !c.oculta);
    const abrir = (linha: LinhaControle) => router.visit(rotas.ver(linha.pedido_id));

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
                            <tr
                                key={linha.id}
                                style={{ background }}
                                tabIndex={0}
                                title="Abrir o pedido"
                                onClick={() => abrir(linha)}
                                onKeyDown={(e) => e.key === 'Enter' && abrir(linha)}
                                className={`cursor-pointer outline-none hover:brightness-95 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-inset ${riscado ? 'line-through' : ''}`}
                            >
                                {visiveis.map((c) => (
                                    <td key={c.chave} className={`min-h-6 border border-zinc-500 px-1 py-1 align-middle whitespace-pre-wrap ${c.alinha === 'left' ? 'text-left' : 'text-center'}`}>
                                        {texto(linha, c, status)}
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
