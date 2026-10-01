export type BlocoChave = 'fornecedor' | 'faturamento' | 'local' | 'cobranca';

export interface Bloco {
    titulo?: string;
    nome?: string;
    endereco?: string[];
    cnpj?: string;
    ie?: string;
    fone?: string;
}

/** Cabeçalho do PDF guardado em pedidos.dados_extras (veja docs/arquitetura.md). */
export interface DadosExtras {
    cond_pgto?: string;
    frete?: string;
    moeda?: string;
    comprador?: string;
    contato_nome?: string;
    contato_email?: string;
    total_icms?: string;
    total_ipi?: string;
    total_produtos?: string;
    observacoes?: string;
    blocos?: Partial<Record<BlocoChave, Bloco>>;
}

export interface Item {
    id?: number;
    item: string | null;
    material: string | null;
    denominacao: string | null;
    qtd: string | null;
    un: string | null;
    preco: string | null;
    vlr_tot: string | null;
    icms: string | null;
    ipi: string | null;
    dt_entrega: string | null;
    item_lei: string | null;
    tipo_manutencao: string | null;
    local_prestacao: string | null;
    desconto_absoluto: string | null;
    icms_monofasico: string | null;
    reducao_base_icms: string | null;
    base_inss: string | null;
}

export interface Pedido {
    id: number;
    numero: string | null;
    data_pedido: string | null;
    cliente: string | null;
    fornecedor: string | null;
    valor: string | null;
    itens_count?: number;
    itens?: Item[];
    dados_extras?: DadosExtras;
    texto_bruto?: string | null;
    tem_pdf?: boolean;
}

export interface Paginador<T> {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

export interface Rotulos {
    blocos: Record<BlocoChave, string>;
    condicoes: Record<string, string>;
}

/** Props compartilhadas por todas as páginas (HandleInertiaRequests). */
export interface PageProps {
    flash: { success?: string | null };
    errors: Record<string, string>;
    [key: string]: unknown;
}
