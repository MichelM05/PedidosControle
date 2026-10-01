import { Badge } from '@/components/ui/badge';
import * as f from '@/lib/format';
import type { Item } from '@/types';

const Dado = ({ rotulo, children }: { rotulo: string; children: React.ReactNode }) => (
    <div>
        <span className="block text-[0.7rem] font-bold uppercase tracking-wide text-muted-foreground">{rotulo}</span>
        {children}
    </div>
);

/** Um item do pedido: valores, impostos e dados do serviço/entrega. */
export default function ItemCard({ item, posicao }: { item: Item; posicao: number }) {
    const divergente =
        item.qtd !== null && item.preco !== null && item.vlr_tot !== null && Math.abs(Number(item.qtd) * Number(item.preco) - Number(item.vlr_tot)) >= 0.01;

    const valores: [string, string][] = [
        ['Qtd.', f.quantidade(item.qtd)],
        ['Un.', f.texto(item.un)],
        ['Preço unit.', f.preco(item.preco)],
        ['ICMS (%)', f.numero(item.icms)],
        ['IPI (%)', f.numero(item.ipi)],
        ['ICMS monofásico', f.numero(item.icms_monofasico)],
        ['Redução base ICMS', f.numero(item.reducao_base_icms)],
        ['Desconto absoluto', f.numero(item.desconto_absoluto)],
        ['Base cálculo INSS (%)', f.numero(item.base_inss)],
    ];

    return (
        <div className="rounded-lg border bg-card p-4 shadow-xs">
            <div className="mb-4 flex justify-between gap-4 border-b pb-3">
                <div>
                    <Badge className="bg-sage text-zinc-900 uppercase">Item {item.item ?? posicao}</Badge>
                    {item.status && (
                        <Badge variant="outline" className="ml-2 uppercase">
                            {item.status}
                            {item.responsavel ? ` · ${item.responsavel}` : ''}
                        </Badge>
                    )}
                    <div className="mt-1 font-bold">{f.texto(item.denominacao)}</div>
                    {item.material && item.material !== item.denominacao && <div className="text-sm text-muted-foreground">Material: {item.material}</div>}
                </div>
                <div className="text-right text-lg font-extrabold whitespace-nowrap">
                    <span className="block text-[0.7rem] font-bold uppercase tracking-wide text-muted-foreground">Valor total</span>
                    {f.moeda(item.vlr_tot)}
                    {divergente && <span className="block text-[0.7rem] font-bold text-destructive">⚠ qtd × preço difere</span>}
                </div>
            </div>

            <dl className="grid gap-x-6 sm:grid-cols-2 lg:grid-cols-4">
                {valores.map(([rotulo, valor]) => (
                    <div key={rotulo} className="flex justify-between border-b border-muted py-1 text-sm">
                        <dt className="text-xs font-bold uppercase text-muted-foreground">{rotulo}</dt>
                        <dd>{valor}</dd>
                    </div>
                ))}
            </dl>

            <div className="mt-4 grid gap-3 rounded-lg bg-muted p-4 text-sm md:grid-cols-2">
                <Dado rotulo="Dt. entrega">{f.data(item.dt_entrega)}</Dado>
                <Dado rotulo="Local da prestação">{f.texto(item.local_prestacao)}</Dado>
                <Dado rotulo="Tipo de manutenção">{f.texto(item.tipo_manutencao)}</Dado>
                <Dado rotulo="Item lei">{f.texto(item.item_lei)}</Dado>
            </div>
        </div>
    );
}
