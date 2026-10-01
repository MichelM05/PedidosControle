import type { ReactNode } from 'react';

/** Estrutura das telas sem login: nome do sistema centralizado e o conteúdo em um cartão. */
export default function GuestLayout({ children }: { children: ReactNode }) {
    return (
        <div className="flex min-h-screen flex-col">
            <main className="flex flex-1 flex-col items-center justify-center px-4 py-12">
                <div className="mb-8 flex items-center gap-3">
                    <img src="/favicon.svg" alt="" className="size-12" />
                    <span className="text-3xl font-black tracking-tight">
                        Pedidos<span className="text-brand">Controle</span>
                    </span>
                </div>
                <div className="w-full max-w-md rounded-2xl border bg-card p-8 shadow-sm">{children}</div>
            </main>
            <footer className="border-t bg-card px-6 py-4 text-center text-sm text-muted-foreground">
                Desenvolvido por <strong className="font-semibold text-foreground">Michel Martins</strong> · {new Date().getFullYear()}
            </footer>
        </div>
    );
}
