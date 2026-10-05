import { CalendarDays, Package } from 'lucide-react';
import { useState } from 'react';

import { ETAPAS } from '@/components/pedidos/EditarControleItem';
import TituloBloco from '@/components/pedidos/TituloBloco';
import { Button } from '@/components/ui/button';
import { diasParaEntrega } from '@/lib/controle';
import * as f from '@/lib/format';
import type { Item } from '@/types';

/** Tom forte da cor da situação, para a faixa lateral (a cor pura é pastel demais para chamar atenção). */
const tomForte = (cor: string) => `color-mix(in srgb, ${cor} 82%, #26261a)`;

const Dado = ({ rotulo, children }: { rotulo: string; children: React.ReactNode }) => (
    <div>
        <span className="block text-[0.7rem] font-bold uppercase tracking-wide text-muted-foreground">{rotulo}</span>
        <span className="font-medium">{children}</span>
    </div>
);

/** "em 5 dias", "hoje", "vencida há 3 dias": só faz sentido para item em andamento. */
function prazo(entrega: string | null, status: string | null): string | null {
    if (!entrega || (status && status !== 'andamento')) return null;
    const dias = diasParaEntrega(entrega);
    if (dias === 0) return 'hoje';
    if (dias === 1) return 'amanhã';
    return dias > 0 ? `em ${dias} dias` : `vencida há ${Math.abs(dias)} ${Math.abs(dias) === 1 ? 'dia' : 'dias'}`;
}

/** Um item do pedido: valores, impostos e dados do serviço/entrega. */
interface Props {
    item: Item;
    posicao: number;
    status: Record<string, string>;
    onStatus: (novo: string) => void;
    onEditarControle: () => void;
    minimizado: boolean;
    onAlternar: () => void;
    onEditar: () => void;
    /** Cor de fundo conforme a situação/prazo do item (null = padrão). */
    cor: string | null;
}

export default function ItemCard({ item, posicao, status, onStatus, onEditarControle, minimizado, onAlternar, onEditar, cor }: Props) {
    const [controleAberto, setControleAberto] = useState(false); // o controle de produção começa minimizado
    const [obsAberta, setObsAberta] = useState(false); // observações podem ser enormes: começam recolhidas
    const linhasObs = item.observacoes ? item.observacoes.split('\n').length : 0;
    const divergente =
        item.qtd !== null && item.preco !== null && item.vlr_tot !== null && Math.abs(Number(item.qtd) * Number(item.preco) - Number(item.vlr_tot)) >= 0.01;
    const aviso = prazo(item.dt_entrega, item.status);

    // Todos os campos aparecem, mesmo os que o PDF não trouxe ("—"): assim se vê o que falta preencher
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
        <div
            className="min-w-0 overflow-hidden rounded-xl border border-l-[8px] bg-card shadow-sm"
            style={{ '--acento': cor ? tomForte(cor) : 'var(--brand)', borderLeftColor: cor ? tomForte(cor) : 'rgba(38,38,26,.18)' } as React.CSSProperties}
        >
            <div className={`flex flex-wrap justify-between gap-4 p-4 ${minimizado ? '' : 'border-b'}`}>
                <div className="min-w-0 flex-1">
                    <div className="flex flex-wrap items-center gap-2">
                        <span
                            className="rounded-md bg-ink px-2.5 py-1 text-xs font-extrabold uppercase tracking-wide text-white"
                            style={cor ? { backgroundColor: `color-mix(in srgb, ${cor} 55%, #1a1a10)` } : undefined}
                        >
                            Item {item.item ?? posicao}
                        </span>
                        <select
                            aria-label="Status do item"
                            value={item.status ?? 'andamento'}
                            onChange={(e) => onStatus(e.target.value)}
                            className="h-7 rounded-full border px-3 text-xs font-bold uppercase outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            style={cor ? { backgroundColor: cor, borderColor: tomForte(cor), color: '#1a1a10' } : undefined}
                        >
                            {Object.entries(status).map(([chave, rotulo]) => (
                                <option key={chave} value={chave}>
                                    {rotulo}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="mt-2 text-base font-extrabold leading-snug break-words">{f.texto(item.denominacao)}</div>

                    {/* Material e entrega ficam sempre à vista, mesmo com o item minimizado */}
                    <div className="mt-2 flex flex-wrap items-center gap-2 text-sm">
                        <span className="inline-flex items-center gap-1.5 rounded-md border bg-background px-2 py-1">
                            <Package className="size-4 text-muted-foreground" />
                            <span className="text-muted-foreground">Material</span>
                            <strong className="break-all">{item.material && item.material !== item.denominacao ? item.material : f.VAZIO}</strong>
                        </span>
                        <span className="inline-flex items-center gap-1.5 rounded-md border-2 border-[var(--acento)] bg-background px-2 py-1">
                            <CalendarDays className="size-4 text-[var(--acento)]" />
                            <span className="text-muted-foreground">Entrega</span>
                            <strong>{f.data(item.dt_entrega)}</strong>
                            {aviso && <span className="text-xs font-semibold text-muted-foreground">({aviso})</span>}
                        </span>
                    </div>
                </div>

                <div className="flex items-start gap-3">
                    <div className="text-right">
                        <span className="block text-[0.7rem] font-bold uppercase tracking-wide text-muted-foreground">Valor total</span>
                        <span className="text-xl font-extrabold whitespace-nowrap">{f.moeda(item.vlr_tot)}</span>
                        {divergente && <span className="block text-[0.7rem] font-bold text-destructive">⚠ qtd × preço difere</span>}
                    </div>
                    <Button type="button" variant="outline" size="sm" onClick={onEditar}>
                        Editar item
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        size="icon-sm"
                        onClick={onAlternar}
                        aria-expanded={!minimizado}
                        aria-label={minimizado ? 'Expandir item' : 'Minimizar item'}
                        title={minimizado ? 'Expandir' : 'Minimizar'}
                    >
                        {minimizado ? '+' : '−'}
                    </Button>
                </div>
            </div>

            {!minimizado && (
                <div className="grid gap-3 p-4">
                    <dl className="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-5">
                        {valores.map(([rotulo, valor]) => (
                            <div key={rotulo} className="rounded-lg bg-muted px-3 py-2">
                                <dt className="text-[0.7rem] font-bold uppercase tracking-wide text-muted-foreground">{rotulo}</dt>
                                <dd className="font-bold">{valor}</dd>
                            </div>
                        ))}
                    </dl>

                    {(
                        <div className="min-w-0 overflow-hidden rounded-lg border p-4 text-sm">
                            <div className="flex items-center justify-between gap-3">
                                <TituloBloco>
                                    Observações {item.observacoes && <span className="font-normal normal-case tracking-normal">({linhasObs} {linhasObs === 1 ? 'linha' : 'linhas'})</span>}
                                </TituloBloco>
                                {item.observacoes && <Button
                                    type="button"
                                    variant="outline"
                                    size="icon-sm"
                                    onClick={() => setObsAberta(!obsAberta)}
                                    aria-expanded={obsAberta}
                                    aria-label={obsAberta ? 'Recolher observações' : 'Ver observações'}
                                    title={obsAberta ? 'Recolher' : 'Ver tudo'}
                                >
                                    {obsAberta ? '−' : '+'}
                                </Button>}
                            </div>
                            {!item.observacoes ? (
                                <p className="mt-1 text-muted-foreground">{f.VAZIO}</p>
                            ) : obsAberta ? (
                                <p className="mt-3 max-h-72 overflow-y-auto whitespace-pre-line break-words text-muted-foreground">{item.observacoes}</p>
                            ) : (
                                <p className="mt-1 overflow-hidden text-ellipsis whitespace-nowrap text-muted-foreground">{item.observacoes.replace(/\s*\n\s*/g, ' · ')}</p>
                            )}
                        </div>
                    )}

                    <div className="grid gap-3 rounded-lg bg-muted p-4 text-sm md:grid-cols-2 lg:grid-cols-4">
                        <Dado rotulo="Fabricante">{f.texto(item.fabricante)}</Dado>
                        <Dado rotulo="Local da prestação">{f.texto(item.local_prestacao)}</Dado>
                        <Dado rotulo="Tipo de manutenção">{f.texto(item.tipo_manutencao)}</Dado>
                        <Dado rotulo="Item lei">{f.texto(item.item_lei)}</Dado>
                    </div>

                    <div className="rounded-lg border-2 border-[var(--acento)] p-4 text-sm">
                        <div className="flex items-center justify-between">
                            <TituloBloco>Controle de produção</TituloBloco>
                            <div className="flex gap-2">
                                <Button type="button" variant="outline" size="sm" onClick={onEditarControle}>
                                    Editar controle
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon-sm"
                                    onClick={() => setControleAberto(!controleAberto)}
                                    aria-expanded={controleAberto}
                                    aria-label={controleAberto ? 'Minimizar controle de produção' : 'Expandir controle de produção'}
                                    title={controleAberto ? 'Minimizar' : 'Expandir'}
                                >
                                    {controleAberto ? '−' : '+'}
                                </Button>
                            </div>
                        </div>
                        {controleAberto && (
                            <div className="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                <Dado rotulo="Status">{f.texto(status[item.status ?? ''] ?? item.status)}</Dado>
                                <Dado rotulo="Responsável">{f.texto(item.responsavel)}</Dado>
                                <Dado rotulo="Cidade entrega">{f.texto(item.cidade_entrega)}</Dado>
                                {Object.entries(ETAPAS).map(([chave, rotulo]) => (
                                    <Dado key={chave} rotulo={rotulo}>
                                        {f.texto(item[chave as keyof Item] as string | null)}
                                    </Dado>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}
