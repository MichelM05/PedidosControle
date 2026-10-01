import { router } from '@inertiajs/react';

import ConfirmDialog from '@/components/ConfirmDialog';
import { Button } from '@/components/ui/button';
import { rotas } from '@/lib/routes';
import type { Pedido } from '@/types';

/** Botão "Excluir" com confirmação. `variant` muda entre botão de tabela e de destaque. */
export default function ExcluirPedido({ pedido, variant = 'outline' }: { pedido: Pedido; variant?: 'outline' | 'destructive' }) {
    const nome = pedido.numero ?? pedido.id;

    return (
        <ConfirmDialog
            title="Excluir pedido"
            description={`Excluir o pedido nº ${nome}? Esta ação não pode ser desfeita.`}
            confirmLabel="Excluir"
            onConfirm={() => router.delete(rotas.excluir(pedido.id))}
        >
            <Button
                variant={variant}
                size={variant === 'outline' ? 'sm' : 'default'}
                className={variant === 'outline' ? 'hover:border-destructive hover:bg-destructive hover:text-white' : undefined}
            >
                Excluir
            </Button>
        </ConfirmDialog>
    );
}
