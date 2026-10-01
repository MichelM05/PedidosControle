import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';

import type { PageProps } from '@/types';

/** Estrutura comum das telas: barra superior, mensagem de sucesso e área de conteúdo. */
export default function AppLayout({ children, largura = 'max-w-6xl' }: { children: ReactNode; largura?: string }) {
    const { flash } = usePage<PageProps>().props;
    const { url } = usePage();
    const naControle = url.startsWith('/controle');

    return (
        <div className="flex min-h-screen flex-col">
            <header className="mb-8 flex flex-wrap items-center gap-x-8 gap-y-2 border-t-[3px] border-t-brand border-b bg-card px-6 py-4">
                <Link href="/" className="text-2xl font-black tracking-tight text-foreground">
                    PDF Transformer
                </Link>
                <nav className="flex gap-1 text-sm font-semibold" aria-label="Principal">
                    {[
                        ['/', 'Pedidos', !naControle],
                        ['/controle', 'Controle', naControle],
                    ].map(([href, rotulo, ativo]) => (
                        <Link key={href as string} href={href as string} aria-current={ativo ? 'page' : undefined} className={ativo ? 'rounded-md bg-primary px-3 py-1.5 text-primary-foreground' : 'rounded-md px-3 py-1.5 text-muted-foreground hover:bg-accent hover:text-foreground'}>
                            {rotulo}
                        </Link>
                    ))}
                </nav>
            </header>

            <main className={`mx-auto w-full ${largura} flex-1 px-4 pb-12`}>
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
