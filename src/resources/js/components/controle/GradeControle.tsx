import { router } from '@inertiajs/react';

import * as f from '@/lib/format';
import { estiloPorSituacao, type Cores, type Prazos } from '@/lib/controle';
import { rotas } from '@/lib/routes';
import type { ColunaControle, LinhaControle } from '@/types';

interface Props {
    linhas: LinhaControle[];
    colunas: ColunaControle[];
    status: Record<string, string>;
    cores: Cores;
    prazos: Prazos;
    mostrarOcultas: boolean;
}

/** Largura em pixels a partir da largura de coluna do Excel. */
const px = (largura: number) => Math.round(largura * 8.4 + 22);

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
export default function GradeControle({ linhas, colunas, status, cores, prazos, mostrarOcultas }: Props) {
    const visiveis = colunas.filter((c) => mostrarOcultas || !c.oculta);
    const abrir = (linha: LinhaControle) => router.visit(rotas.ver(linha.pedido_id));

    return (
        <div className="max-h-[70vh] overflow-auto rounded-lg border border-olive bg-white">
            <table className="border-collapse text-sm" style={{ tableLayout: 'fixed', width: visiveis.reduce((total, c) => total + px(c.largura), 0) }}>
                <colgroup>
                    {visiveis.map((c) => (
                        <col key={c.chave} style={{ width: px(c.largura) }} />
                    ))}
                </colgroup>
                <thead className="sticky top-0 z-10">
                    <tr className="h-16">
                        {visiveis.map((c) => (
                            <th
                                key={c.chave}
                                style={{ background: `#${c.cor}` }}
                                className={`border border-olive/70 px-3 text-xs font-bold whitespace-pre-line ${c.alinha === 'left' ? 'text-left' : 'text-center'}`}
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
                        const { background, riscado } = estiloPorSituacao(linha.status, linha.dt_entrega, cores, prazos);

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
                                    <td key={c.chave} className={`min-h-10 border border-olive/70 px-3 py-2.5 align-middle whitespace-pre-wrap ${c.alinha === 'left' ? 'text-left' : 'text-center'}`}>
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
