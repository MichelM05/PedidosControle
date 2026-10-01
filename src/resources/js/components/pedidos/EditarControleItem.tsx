import { useForm } from '@inertiajs/react';

import Field from '@/components/Field';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { rotas } from '@/lib/routes';
import type { Item } from '@/types';

/** Etapas do controle de produção (chave => rótulo). Espelha PedidoItem::ETAPAS. */
export const ETAPAS: Record<string, string> = {
    desenho_nesting: 'Desenho nesting',
    compra_mp: 'Compra M.P',
    compra_insumo: 'Compra insumo',
    usinagem: 'Usinagem',
    corte_dobra: 'Corte e/ou dobra',
    solda: 'Solda',
    pintura: 'Pintura',
    montagem: 'Montagem',
};

interface Props {
    item: Item;
    status: Record<string, string>;
    onClose: () => void;
}

/** Modal para editar o controle de produção de um item (alimenta a planilha de controle). */
export default function EditarControleItem({ item, status, onClose }: Props) {
    const form = useForm<Record<string, string>>({
        dt_entrega: item.dt_entrega ?? '',
        cidade_entrega: item.cidade_entrega ?? '',
        responsavel: item.responsavel ?? '',
        status: item.status ?? 'andamento',
        ...Object.fromEntries(Object.keys(ETAPAS).map((chave) => [chave, (item[chave as keyof Item] as string | null) ?? ''])),
    });

    return (
        <Dialog open onOpenChange={(aberto) => !aberto && onClose()}>
            <DialogContent className="max-w-2xl">
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.patch(rotas.controleItem(item.id as number), { preserveScroll: true, onSuccess: onClose });
                    }}
                    className="grid gap-4"
                >
                    <DialogHeader>
                        <DialogTitle>Controle de produção</DialogTitle>
                        <DialogDescription>{item.denominacao ?? 'Item'}. Etapas aceitam data ou texto (ex.: 12/03/2026, "recebido 02/09", "xxxxx").</DialogDescription>
                    </DialogHeader>

                    <div className="grid max-h-[60vh] gap-3 overflow-y-auto p-1 sm:grid-cols-2">
                        <Field id="ctl-dt_entrega" label="Data de entrega" type="date" value={form.data.dt_entrega} onChange={(v) => form.setData('dt_entrega', v)} error={form.errors.dt_entrega} />
                        <Field id="ctl-cidade_entrega" label="Cidade entrega" value={form.data.cidade_entrega} onChange={(v) => form.setData('cidade_entrega', v)} error={form.errors.cidade_entrega} />
                        {Object.entries(ETAPAS).map(([chave, rotulo]) => (
                            <Field key={chave} id={`ctl-${chave}`} label={rotulo} placeholder="data ou texto" value={form.data[chave]} onChange={(v) => form.setData(chave, v)} error={form.errors[chave]} />
                        ))}
                        <Field id="ctl-responsavel" label="Responsável" value={form.data.responsavel} onChange={(v) => form.setData('responsavel', v)} error={form.errors.responsavel} />
                        <div className="grid gap-1.5">
                            <Label htmlFor="ctl-status" className="text-xs font-bold uppercase tracking-wide text-muted-foreground">
                                Status
                            </Label>
                            <select
                                id="ctl-status"
                                value={form.data.status}
                                onChange={(e) => form.setData('status', e.target.value)}
                                className="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            >
                                {Object.entries(status).map(([chave, rotulo]) => (
                                    <option key={chave} value={chave}>
                                        {rotulo}
                                    </option>
                                ))}
                            </select>
                            {form.errors.status && <span className="text-xs text-destructive">{form.errors.status}</span>}
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={onClose}>
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            Salvar
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
