import { Link } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import type { Paginador } from '@/types';

export default function Paginacao({ paginador }: { paginador: Paginador<unknown> }) {
    if (paginador.last_page <= 1) return null;

    const passo = (rotulo: string, url: string | null) =>
        url ? (
            <Button asChild variant="outline" size="sm">
                <Link href={url} preserveScroll>
                    {rotulo}
                </Link>
            </Button>
        ) : (
            <Button variant="outline" size="sm" disabled>
                {rotulo}
            </Button>
        );

    return (
        <nav aria-label="Paginação" className="mt-6 flex flex-wrap items-center justify-between gap-3 text-sm text-muted-foreground">
            <span>
                Mostrando {paginador.from}–{paginador.to} de {paginador.total}
            </span>
            <div className="flex items-center gap-3">
                {passo('← Anterior', paginador.prev_page_url)}
                <span>
                    Página {paginador.current_page} de {paginador.last_page}
                </span>
                {passo('Próxima →', paginador.next_page_url)}
            </div>
        </nav>
    );
}
