/** Título de bloco do item (visualização e edição): barrinha de marca à esquerda + texto em maiúsculas. */
export default function TituloBloco({ children, className }: { children: React.ReactNode; className?: string }) {
    return (
        <span className={`flex items-center gap-2 text-xs font-extrabold uppercase tracking-wide ${className ?? ''}`}>
            <span className="h-3.5 w-1 rounded-full bg-[var(--acento,var(--brand))]" />
            {children}
        </span>
    );
}
