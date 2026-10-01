import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';

import type { PageProps } from '@/types';

/** Estrutura comum das telas: barra superior, mensagem de sucesso e área de conteúdo. */
export default function AppLayout({ children }: { children: ReactNode }) {
    const { flash } = usePage<PageProps>().props;

    return (
        <div className="flex min-h-screen flex-col">
            <header className="mb-8 border-t-[3px] border-t-brand border-b bg-card px-6 py-4">
                <Link href="/" className="text-2xl font-black tracking-tight text-foreground">
                    PDF Transformer
                </Link>
            </header>

            <main className="mx-auto w-full max-w-6xl flex-1 px-4 pb-12">
                {flash.success && (
                    <div role="status" className="mb-6 rounded-xl border border-l-4 border-l-sage bg-card px-5 py-4 text-sm">
                        {flash.success}
                    </div>
                )}
                {children}
            </main>

            <footer className="border-t bg-card px-6 py-4 text-center text-sm text-muted-foreground">
                Desenvolvido por <strong className="font-semibold text-foreground">Michel Martins</strong> · {new Date().getFullYear()}
            </footer>
        </div>
    );
}
