import type { Cores, Prazos } from '@/lib/controle';

/** Legenda das cores de situação e prazo (a mesma na lista de pedidos e na grade de controle). */
export default function LegendaCores({ cores, prazos }: { cores: Cores; prazos: Prazos }) {
    const itens: [string, string][] = [
        [cores.linha, `Em andamento (entrega em mais de ${prazos.alerta} dias)`],
        [cores.alerta, `Entrega em até ${prazos.alerta} dias`],
        [cores.urgente, `Entrega em até ${prazos.urgente} dias ou vencida`],
        [cores.finalizado, 'Finalizado'],
        [cores.entregue, 'Entregue'],
        [cores.cancelado, 'Cancelado'],
    ];

    return (
        <ul className="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-xs text-muted-foreground">
            {itens.map(([cor, rotulo]) => (
                <li key={rotulo} className="flex items-center gap-1.5">
                    <span className="inline-block size-3 rounded-sm border border-olive" style={{ background: `#${cor}` }} /> {rotulo}
                </li>
            ))}
        </ul>
    );
}
