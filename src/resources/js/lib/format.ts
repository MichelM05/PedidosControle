/** Formatação pt-BR para exibição. Devolve "—" quando não há valor. */
export const VAZIO = '—';

type Valor = string | number | null | undefined;

const vazio = (v: Valor): v is null | undefined | '' => v === null || v === undefined || v === '';

export function numero(v: Valor, casas = 2): string {
    if (vazio(v)) return VAZIO;
    return Number(v).toLocaleString('pt-BR', { minimumFractionDigits: casas, maximumFractionDigits: casas });
}

export function moeda(v: Valor): string {
    return vazio(v) ? VAZIO : `R$ ${numero(v)}`;
}

/** Preço unitário: até 4 casas, sem zeros à direita além das 2 primeiras (igual ao PDF). */
export function preco(v: Valor): string {
    if (vazio(v)) return VAZIO;
    return numero(v, 4).replace(/(\d,\d{2})0{1,2}$/, '$1');
}

export function quantidade(v: Valor): string {
    if (vazio(v)) return VAZIO;
    return numero(v, 4).replace(/0+$/, '').replace(/,$/, '');
}

/** "2026-02-11" → "11/02/2026" (sem passar por Date, para não sofrer com fuso horário). */
export function data(v: string | null | undefined): string {
    const m = v?.match(/^(\d{4})-(\d{2})-(\d{2})/);
    return m ? `${m[3]}/${m[2]}/${m[1]}` : VAZIO;
}

export function cnpj(v: string | null | undefined): string {
    if (!v?.trim()) return VAZIO;
    const d = v.replace(/\D/g, '');
    return d.length === 14 ? d.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5') : v;
}

export function texto(v: string | null | undefined): string {
    return v?.trim() ? v : VAZIO;
}

/** Data e hora no fuso do navegador: "03/10/2026 14:32". */
export function dataHora(iso: string | null | undefined): string {
    if (!iso) return VAZIO;
    return new Date(iso).toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}
