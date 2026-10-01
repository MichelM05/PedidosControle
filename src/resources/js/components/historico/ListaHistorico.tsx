import { Link } from '@inertiajs/react';

import * as f from '@/lib/format';
import { rotas } from '@/lib/routes';
import type { GrupoHistorico, RegistroHistorico } from '@/types';

const ROTULO_ACAO = { criou: 'Criou', editou: 'Editou', excluiu: 'Excluiu' } as const;

/** Uma linha do histórico, em texto: o quê mudou, de qual valor para qual. */
function Mudanca({ r }: { r: RegistroHistorico }) {
    const alvo = r.item_id || r.item_descricao ? `o item ${r.item_descricao ?? ''}`.trim() : 'o pedido';

    if (r.acao !== 'editou') {
        return (
            <span>
                <strong>{ROTULO_ACAO[r.acao]}</strong> {alvo}
                {r.valor_novo ? <span className="text-muted-foreground"> — {r.valor_novo}</span> : null}
            </span>
        );
    }

    return (
        <span>
            {r.item_descricao && <span className="text-muted-foreground">Item {r.item_descricao} · </span>}
            <strong>{r.campo}</strong>:{' '}
            <span className="text-muted-foreground line-through decoration-1">{r.valor_anterior ?? '(vazio)'}</span> → <strong>{r.valor_novo ?? '(vazio)'}</strong>
        </span>
    );
}

interface Props {
    grupos: GrupoHistorico[];
    /** Mostra o número do pedido (com link) no cabeçalho de cada grupo. */
    mostrarPedido?: boolean;
}

/** Histórico agrupado por salvamento: "quando · quem · pedido" e a lista do que mudou. */
export default function ListaHistorico({ grupos, mostrarPedido = true }: Props) {
    if (grupos.length === 0) return <p className="py-8 text-center text-sm text-muted-foreground">Nenhuma alteração registrada.</p>;

    return (
        <ol className="grid gap-3">
            {grupos.map((g) => (
                <li key={g.lote} className="rounded-lg border bg-card p-4 text-sm">
                    <div className="mb-2 flex flex-wrap items-center gap-x-3 gap-y-1 border-b pb-2">
                        <span className="font-semibold">{f.dataHora(g.quando)}</span>
                        <span className="rounded-full bg-sage/60 px-2 py-0.5 text-xs font-semibold">{g.usuario}</span>
                        {mostrarPedido && g.pedido_numero !== null && (
                            <span>
                                {g.pedido_id ? (
                                    <Link href={rotas.ver(g.pedido_id)} className="underline-offset-2 hover:underline">
                                        Pedido nº {g.pedido_numero}
                                    </Link>
                                ) : (
                                    <>Pedido nº {g.pedido_numero}</>
                                )}
                            </span>
                        )}
                        {g.origem && <span className="text-xs text-muted-foreground">{g.origem}</span>}
                    </div>
                    <ul className="grid gap-1">
                        {g.registros.map((r) => (
                            <li key={r.id}>
                                <Mudanca r={r} />
                            </li>
                        ))}
                    </ul>
                </li>
            ))}
        </ol>
    );
}
