import { ChevronDown, ChevronUp, X } from 'lucide-react';

import Field from '@/components/Field';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import type { Item } from '@/types';

type Chave = keyof Item;
interface Campo {
    nome: Chave;
    rotulo: string;
    tipo?: 'text' | 'number' | 'date';
    span?: number;
    placeholder?: string;
    /** Campo com opções fixas (as opções vêm do servidor). */
    opcoes?: 'status';
}

const DEC = { tipo: 'number' } as const;

/** Seções do card de item. Para um novo campo por item, acrescente aqui (veja docs/parser-pdf.md). */
const SECOES: { titulo: string; colunas: string; campos: Campo[] }[] = [
    {
        titulo: 'Identificação',
        colunas: 'sm:grid-cols-4',
        campos: [
            { nome: 'item', rotulo: 'Item', placeholder: '10' },
            { nome: 'material', rotulo: 'Material', placeholder: 'Cód. material' },
            { nome: 'denominacao', rotulo: 'Denominação', span: 2, placeholder: 'Descrição' },
        ],
    },
    {
        titulo: 'Quantidade e valores',
        colunas: 'sm:grid-cols-4',
        campos: [
            { nome: 'qtd', rotulo: 'Quantidade', ...DEC },
            { nome: 'un', rotulo: 'Unidade', placeholder: 'UN' },
            { nome: 'preco', rotulo: 'Preço unit.', ...DEC },
            { nome: 'vlr_tot', rotulo: 'Valor total', ...DEC },
        ],
    },
    {
        titulo: 'Impostos e ajustes',
        colunas: 'sm:grid-cols-3',
        campos: [
            { nome: 'icms', rotulo: 'ICMS (%)', ...DEC },
            { nome: 'ipi', rotulo: 'IPI (%)', ...DEC },
            { nome: 'icms_monofasico', rotulo: 'ICMS monofásico', ...DEC },
            { nome: 'reducao_base_icms', rotulo: 'Redução base ICMS', ...DEC },
            { nome: 'desconto_absoluto', rotulo: 'Desconto absoluto', ...DEC },
            { nome: 'base_inss', rotulo: 'Base cálculo INSS (%)', ...DEC },
        ],
    },
    {
        titulo: 'Serviço / entrega',
        colunas: 'sm:grid-cols-3',
        campos: [
            { nome: 'dt_entrega', rotulo: 'Dt. entrega', tipo: 'date' },
            { nome: 'local_prestacao', rotulo: 'Local da prestação', span: 2 },
            { nome: 'cidade_entrega', rotulo: 'Cidade entrega (planilha de controle)' },
            { nome: 'tipo_manutencao', rotulo: 'Tipo de manutenção', span: 2 },
            { nome: 'item_lei', rotulo: 'Item lei', span: 3 },
        ],
    },
    {
        titulo: 'Controle de produção',
        colunas: 'sm:grid-cols-4',
        campos: [
            { nome: 'desenho_nesting', rotulo: 'Desenho nesting', placeholder: 'data ou texto' },
            { nome: 'compra_mp', rotulo: 'Compra M.P', placeholder: 'data ou texto' },
            { nome: 'compra_insumo', rotulo: 'Compra insumo', placeholder: 'data ou texto' },
            { nome: 'usinagem', rotulo: 'Usinagem', placeholder: 'data ou texto' },
            { nome: 'corte_dobra', rotulo: 'Corte e/ou dobra', placeholder: 'data ou texto' },
            { nome: 'solda', rotulo: 'Solda', placeholder: 'data ou texto' },
            { nome: 'pintura', rotulo: 'Pintura', placeholder: 'data ou texto' },
            { nome: 'montagem', rotulo: 'Montagem', placeholder: 'data ou texto' },
            { nome: 'responsavel', rotulo: 'Responsável', span: 2 },
            { nome: 'status', rotulo: 'Status', span: 2, opcoes: 'status' },
        ],
    },
];

const SPAN: Record<number, string> = { 2: 'sm:col-span-2', 3: 'sm:col-span-3' };

interface Props {
    item: Item;
    posicao: number;
    minimizado: boolean;
    erros: Record<string, string>;
    status: Record<string, string>;
    onChange: (campo: Chave, valor: string) => void;
    onToggle: () => void;
    onRemove: () => void;
}

/** Card de edição de um item (usado no formulário de criar/editar pedido). */
export default function ItemFormCard({ item, posicao, minimizado, erros, status, onChange, onToggle, onRemove }: Props) {
    return (
        <div className="rounded-xl border border-l-4 border-l-brand bg-card p-5">
            <div className={cn('flex items-center justify-between border-b pb-2', !minimizado && 'mb-4')}>
                <span className="font-bold">Item #{posicao}</span>
                <div className="flex gap-1">
                    <Button type="button" variant="ghost" size="icon-sm" onClick={onToggle} aria-label={minimizado ? 'Expandir item' : 'Minimizar item'}>
                        {minimizado ? <ChevronDown /> : <ChevronUp />}
                    </Button>
                    <Button type="button" variant="ghost" size="icon-sm" onClick={onRemove} aria-label="Remover item" className="hover:bg-destructive hover:text-white">
                        <X />
                    </Button>
                </div>
            </div>

            {!minimizado &&
                SECOES.map((secao) => (
                    <fieldset key={secao.titulo} className="mb-5 last:mb-0">
                        <legend className="mb-2 text-xs font-extrabold uppercase tracking-wide text-muted-foreground">{secao.titulo}</legend>
                        <div className={cn('grid gap-x-4 gap-y-3', secao.colunas)}>
                            {secao.campos.map((c) =>
                                c.opcoes ? (
                                    <div key={c.nome} className={cn('grid gap-1.5', c.span && SPAN[c.span])}>
                                        <Label htmlFor={`item-${posicao}-${c.nome}`} className="text-xs font-bold uppercase tracking-wide text-muted-foreground">
                                            {c.rotulo}
                                        </Label>
                                        <select
                                            id={`item-${posicao}-${c.nome}`}
                                            value={item.status ?? 'andamento'}
                                            onChange={(e) => onChange(c.nome, e.target.value)}
                                            className="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                        >
                                            {Object.entries(status).map(([chave, rotulo]) => (
                                                <option key={chave} value={chave}>
                                                    {rotulo}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                ) : (
                                <Field
                                    key={c.nome}
                                    id={`item-${posicao}-${c.nome}`}
                                    label={c.rotulo}
                                    type={c.tipo ?? 'text'}
                                    step={c.tipo === 'number' ? '0.0001' : undefined}
                                    placeholder={c.placeholder}
                                    className={c.span ? SPAN[c.span] : undefined}
                                    value={item[c.nome] as string | null}
                                    onChange={(v) => onChange(c.nome, v)}
                                    error={erros[`itens.${posicao - 1}.${c.nome}`]}
                                />
                                ),
                            )}
                        </div>
                    </fieldset>
                ))}
        </div>
    );
}
