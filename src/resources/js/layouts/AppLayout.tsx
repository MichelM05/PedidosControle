import { Link, router, usePage } from '@inertiajs/react';
import { LogOut, User } from 'lucide-react';
import type { ReactNode } from 'react';

import { cn } from '@/lib/utils';
import type { PageProps } from '@/types';

/** Estrutura comum das telas: barra superior, mensagem de sucesso e área de conteúdo. */
export default function AppLayout({ children, largura = 'max-w-6xl' }: { children: ReactNode; largura?: string }) {
    const { flash, auth } = usePage<PageProps>().props;
    const usuario = auth.user;
    const { url } = usePage();
    const naControle = url.startsWith('/controle');
    const nosUsuarios = url.startsWith('/usuarios');
    const noPerfil = url.startsWith('/perfil');

    return (
        <div className="flex min-h-screen flex-col">
            <header className="mb-8 bg-primary text-primary-foreground shadow-md">
                <div className="mx-auto flex max-w-[110rem] flex-wrap items-center gap-x-10 gap-y-3 px-6 py-4">
                    <Link href="/" className="flex items-center gap-3">
                        <img src="/favicon.svg" alt="" className="size-10" />
                        <span className="text-2xl font-black tracking-tight sm:text-3xl">
                            Pedidos<span className="text-white">Controle</span>
                        </span>
                    </Link>
                    <nav className="flex gap-2" aria-label="Principal">
                        {[
                            ['/', 'Pedidos', !naControle && !nosUsuarios && !noPerfil],
                            ['/controle', 'Controle', naControle],
                            ...(usuario?.is_admin ? [['/usuarios', 'Usuários', nosUsuarios]] : []),
                        ].map(([href, rotulo, ativo]) => (
                            <Link
                                key={href as string}
                                href={href as string}
                                aria-current={ativo ? 'page' : undefined}
                                className={cn(
                                    'rounded-lg px-5 py-2 text-base font-bold transition-colors',
                                    ativo ? 'bg-white text-ink shadow-sm' : 'text-ink hover:bg-black/10',
                                )}
                            >
                                {rotulo}
                            </Link>
                        ))}
                    </nav>

                    {usuario && (
                        <div className="ml-auto flex items-center gap-2">
                            <Link
                                href="/perfil"
                                title="Meu perfil"
                                className={cn('flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold hover:bg-black/10', noPerfil && 'bg-black/10')}
                            >
                                <User className="size-4" />
                                {usuario.name}
                            </Link>
                            <button
                                type="button"
                                onClick={() => router.post('/logout')}
                                title="Sair"
                                className="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold hover:bg-black/10"
                            >
                                <LogOut className="size-4" /> Sair
                            </button>
                        </div>
                    )}
                </div>
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
