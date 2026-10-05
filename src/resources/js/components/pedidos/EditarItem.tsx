import { useForm } from '@inertiajs/react';

import { SecoesDoItem } from '@/components/pedidos/ItemFormCard';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { rotas } from '@/lib/routes';
import type { Item } from '@/types';

interface Props {
    item: Item;
    posicao: number;
    status: Record<string, string>;
    onClose: () => void;
}

/** Modal para editar um item do pedido (todos os campos), sem sair da tela de detalhes. */
export default function EditarItem({ item, posicao, status, onClose }: Props) {
    const { id, ...campos } = item;
    const form = useForm<Record<string, string>>(Object.fromEntries(Object.entries(campos).map(([k, v]) => [k, v === null || v === undefined ? '' : String(v)])));

    return (
        <Dialog open onOpenChange={(aberto) => !aberto && onClose()}>
            <DialogContent className="sm:max-w-6xl">
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.patch(rotas.item(id as number), { preserveScroll: true, onSuccess: onClose });
                    }}
                    className="grid gap-4"
                >
                    <DialogHeader>
                        <DialogTitle>Editar item {item.item ?? posicao}</DialogTitle>
                        <DialogDescription className="sr-only">Edite os campos do item e salve.</DialogDescription>
                    </DialogHeader>

                    <div className="max-h-[75vh] overflow-y-auto p-1">
                        <SecoesDoItem
                            item={form.data as unknown as Item}
                            idBase={`editar-item-${id}`}
                            erros={form.errors}
                            prefixoErro=""
                            status={status}
                            onChange={(campo, valor) => form.setData(campo as string, valor)}
                        />
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
