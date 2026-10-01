/** Cores e regras de prazo do controle de pedidos (espelham App\Support\ColunasControle e a exportação .xlsx). */

export interface Cores {
    linha: string;
    entregue: string;
    finalizado: string;
    cancelado: string;
    alerta: string;
    urgente: string;
}

export interface Prazos {
    alerta: number;
    urgente: number;
}

/** Dias entre hoje e a data de entrega ("AAAA-MM-DD" lida como data local; negativo = já passou). */
export function diasParaEntrega(data: string): number {
    const [a, m, d] = data.split('-').map(Number);
    const hoje = new Date();
    hoje.setHours(0, 0, 0, 0);
    return Math.round((new Date(a, m - 1, d).getTime() - hoje.getTime()) / 86_400_000);
}

/**
 * Cor e estilo por situação e prazo, na mesma ordem de prioridade da planilha exportada: situação (cancelado, entregue,
 * finalizado) e, para itens em andamento, o prazo: vermelho em até `urgente` dias (ou vencido), amarelo em até `alerta` dias.
 */
export function estiloPorSituacao(status: string | null | undefined, entrega: string | null | undefined, cores: Cores, prazos: Prazos): { background: string; riscado: boolean } {
    if (status === 'cancelado') return { background: `#${cores.cancelado}`, riscado: true };
    if (status === 'entregue') return { background: `#${cores.entregue}`, riscado: true };
    if (status === 'finalizado') return { background: `#${cores.finalizado}`, riscado: false };

    if (entrega) {
        const dias = diasParaEntrega(entrega);
        if (dias <= prazos.urgente) return { background: `#${cores.urgente}`, riscado: false };
        if (dias <= prazos.alerta) return { background: `#${cores.alerta}`, riscado: false };
    }
    return { background: `#${cores.linha}`, riscado: false };
}
